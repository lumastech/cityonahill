<?php

use App\Data\CreateOtherIncomeData;
use App\Models\AcademicYear;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\OtherIncome;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\Term;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'FR']);
    $this->service = app(FinanceService::class);
    $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id, 'name' => '2026']);
    $this->term = Term::factory()->create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id]);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 5]);
    $this->stream = Stream::factory()->create(['school_id' => $this->school->id, 'grade_id' => $this->grade->id]);
    $this->cashier = User::factory()->create(['school_id' => $this->school->id]);
    $this->feeStructure = FeeStructure::create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'term_id' => $this->term->id,
        'academic_year_id' => $this->academicYear->id,
        'name' => 'Term Fees',
        'amount' => 1500.00,
        'applies_to' => 'all',
        'is_mandatory' => true,
    ]);
    $this->pupil = Pupil::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'status' => 'active',
    ]);
});

function makeInvoice(array $overrides = []): FeeInvoice
{
    return FeeInvoice::create(array_merge([
        'school_id' => test()->school->id,
        'pupil_id' => test()->pupil->id,
        'fee_structure_id' => test()->feeStructure->id,
        'term_id' => test()->term->id,
        'academic_year_id' => test()->academicYear->id,
        'amount' => 1500.00,
        'discount' => 0,
        'balance_due' => 1500.00,
        'status' => 'unpaid',
    ], $overrides));
}

it('records other income with the recording user', function () {
    $income = $this->service->recordOtherIncome(
        $this->school->id,
        new CreateOtherIncomeData(source: 'donation', description: 'PTA gift', amount: 2000.00, received_date: '2026-07-05', reference: 'PTA-1'),
        $this->cashier->id,
    );

    expect($income)->toBeInstanceOf(OtherIncome::class)
        ->and((float) $income->amount)->toBe(2000.00)
        ->and($income->recorded_by)->toBe($this->cashier->id);
});

it('profit and loss nets income against expenses within the period only', function () {
    $invoice = makeInvoice();

    // in-period fee payment
    FeePayment::create([
        'school_id' => $this->school->id, 'pupil_id' => $this->pupil->id, 'invoice_id' => $invoice->id,
        'amount' => 1000.00, 'payment_method' => 'cash', 'received_by' => $this->cashier->id, 'payment_date' => '2026-07-10',
    ]);
    // out-of-period fee payment — excluded
    FeePayment::create([
        'school_id' => $this->school->id, 'pupil_id' => $this->pupil->id, 'invoice_id' => $invoice->id,
        'amount' => 9999.00, 'payment_method' => 'cash', 'received_by' => $this->cashier->id, 'payment_date' => '2026-09-01',
    ]);

    OtherIncome::create([
        'school_id' => $this->school->id, 'source' => 'grant', 'description' => 'grant', 'amount' => 500.00, 'received_date' => '2026-07-15',
    ]);
    Expense::create([
        'school_id' => $this->school->id, 'category' => 'utilities', 'description' => 'power', 'amount' => 300.00, 'expense_date' => '2026-07-20',
    ]);

    $pl = $this->service->getProfitAndLoss($this->school->id, '2026-07-01', '2026-07-31');

    expect($pl['fees_collected'])->toBe(1000.00)
        ->and($pl['other_income_total'])->toBe(500.00)
        ->and($pl['total_income'])->toBe(1500.00)
        ->and($pl['total_expenses'])->toBe(300.00)
        ->and($pl['net'])->toBe(1200.00);
});

it('ages receivables into buckets by outstanding balance', function () {
    // fully-due-in-future invoice partly paid -> 'current' bucket, outstanding 500
    $current = makeInvoice(['due_date' => '2026-08-30', 'balance_due' => 1500.00]);
    FeePayment::create([
        'school_id' => $this->school->id, 'pupil_id' => $this->pupil->id, 'invoice_id' => $current->id,
        'amount' => 1000.00, 'payment_method' => 'cash', 'received_by' => $this->cashier->id, 'payment_date' => '2026-07-01',
    ]);

    // overdue ~45 days as of 2026-07-15 -> '31_60' bucket, outstanding 1500 (second structure to avoid unique clash)
    $structure2 = FeeStructure::create([
        'school_id' => $this->school->id, 'grade_id' => $this->grade->id, 'term_id' => $this->term->id,
        'academic_year_id' => $this->academicYear->id, 'name' => 'Exam Fees', 'amount' => 1500.00,
        'applies_to' => 'all', 'is_mandatory' => true,
    ]);
    makeInvoice(['fee_structure_id' => $structure2->id, 'due_date' => '2026-06-01', 'balance_due' => 1500.00]);

    $aging = $this->service->getReceivablesAging($this->school->id, '2026-07-15');

    $byKey = collect($aging['buckets'])->keyBy('key');

    expect($byKey['current']['amount'])->toBe(500.00)
        ->and($byKey['31_60']['amount'])->toBe(1500.00)
        ->and($aging['total_outstanding'])->toBe(2000.00)
        ->and($aging['debtors'])->toHaveCount(1)
        ->and($aging['debtors'][0]['outstanding'])->toBe(2000.00);
});

it('reconciling a gateway payment preserves the net billed balance_due', function () {
    $invoice = makeInvoice(['balance_due' => 1500.00]);

    FeePayment::create([
        'school_id' => $this->school->id, 'pupil_id' => $this->pupil->id, 'invoice_id' => $invoice->id,
        'amount' => 1500.00, 'payment_method' => 'mtn_momo', 'received_by' => $this->cashier->id,
        'payment_date' => '2026-07-10', 'gateway' => 'momo', 'gateway_status' => 'completed',
    ]);

    $this->service->reconcileInvoice($invoice);
    $invoice->refresh();

    expect($invoice->status)->toBe('paid')
        ->and((float) $invoice->balance_due)->toBe(1500.00)
        ->and($invoice->outstanding)->toBe(0.0);
});
