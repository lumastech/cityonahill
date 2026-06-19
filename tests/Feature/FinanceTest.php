<?php

use App\Data\BulkRaiseInvoicesData;
use App\Data\RecordPaymentData;
use App\Models\AcademicYear;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\GuardianPortalAccess;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\Term;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\ParentPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'FIN']);
    $this->service = app(FinanceService::class);
    $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id, 'name' => '2026']);
    $this->term = Term::factory()->create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id]);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 8]);
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

it('invoice status updated to partial on part payment', function () {
    $invoice = FeeInvoice::create([
        'school_id' => $this->school->id,
        'pupil_id' => $this->pupil->id,
        'fee_structure_id' => $this->feeStructure->id,
        'term_id' => $this->term->id,
        'academic_year_id' => $this->academicYear->id,
        'amount' => 1500.00,
        'discount' => 0,
        'balance_due' => 1500.00,
        'status' => 'unpaid',
    ]);

    $payData = new RecordPaymentData(
        invoice_id: $invoice->id,
        amount: 500.00,
        payment_method: 'cash',
        reference: null,
        transaction_id: null,
        payment_date: '2026-07-01',
        mobile_money_provider: null,
    );

    $this->service->recordPayment($payData, $this->cashier->id);

    $invoice->refresh();

    expect($invoice->status)->toBe('partial');
});

it('invoice status updated to paid when fully settled', function () {
    $invoice = FeeInvoice::create([
        'school_id' => $this->school->id,
        'pupil_id' => $this->pupil->id,
        'fee_structure_id' => $this->feeStructure->id,
        'term_id' => $this->term->id,
        'academic_year_id' => $this->academicYear->id,
        'amount' => 1500.00,
        'discount' => 0,
        'balance_due' => 1500.00,
        'status' => 'unpaid',
    ]);

    $payData = new RecordPaymentData(
        invoice_id: $invoice->id,
        amount: 1500.00,
        payment_method: 'bank_transfer',
        reference: 'REF-001',
        transaction_id: null,
        payment_date: '2026-07-01',
        mobile_money_provider: null,
    );

    $this->service->recordPayment($payData, $this->cashier->id);

    $invoice->refresh();

    expect($invoice->status)->toBe('paid');
    expect(FeePayment::where('invoice_id', $invoice->id)->count())->toBe(1);
});

it('bulk invoices raised for all active pupils in grade', function () {
    $pupil2 = Pupil::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'status' => 'active',
    ]);

    // withdrawn pupil — should be excluded (not 'active')
    Pupil::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'status' => 'withdrawn',
    ]);

    $data = new BulkRaiseInvoicesData(
        fee_structure_id: $this->feeStructure->id,
        term_id: $this->term->id,
        grade_id: $this->grade->id,
    );

    $count = $this->service->bulkRaiseInvoices($this->school->id, $data);

    // $this->pupil + $pupil2 = 2 active pupils in grade
    expect($count)->toBe(2)
        ->and(FeeInvoice::where('school_id', $this->school->id)->count())->toBe(2);
});

it('budget vs actual calculated correctly', function () {
    Budget::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->academicYear->id,
        'term_id' => null,
        'category' => 'salaries',
        'amount' => 50_000,
    ]);

    Expense::create([
        'school_id' => $this->school->id,
        'category' => 'salaries',
        'description' => 'June salaries',
        'amount' => 45_000,
        'expense_date' => '2026-06-30',
    ]);

    $result = $this->service->getBudgetVsActual($this->school->id, $this->academicYear->id);

    $row = collect($result['by_category'])->firstWhere('category', 'salaries');

    expect($row['budget'])->toEqual(50_000.0)
        ->and($row['actual'])->toEqual(45_000.0)
        ->and($row['variance'])->toEqual(5_000.0);
});

it('parent can view own child fee statement only', function () {
    $parentUser = User::factory()->create(['school_id' => $this->school->id]);
    $guardian = Guardian::factory()->create(['school_id' => $this->school->id]);

    GuardianPortalAccess::create([
        'guardian_id' => $guardian->id,
        'user_id' => $parentUser->id,
        'activated_at' => now(),
    ]);

    // Link pupil to guardian
    $this->pupil->guardians()->attach($guardian->id, ['is_primary' => true]);

    // Other school's pupil — access should be denied
    $otherSchool = School::factory()->create(['code' => 'OTH']);
    $otherPupil = Pupil::factory()->create(['school_id' => $otherSchool->id]);

    $portalService = app(ParentPortalService::class);

    // Accessing own child should work
    $statement = $this->service->getPupilFeeStatement($this->pupil->id, $this->term->id);
    expect($statement)->toHaveKey('invoices')
        ->and($statement)->toHaveKey('outstanding');

    // Accessing unlinked pupil as parent should throw
    expect(fn () => $portalService->getChildSummary($parentUser->id, $otherPupil->id))
        ->toThrow(HttpException::class);
});
