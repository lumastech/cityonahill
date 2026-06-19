<?php

namespace App\Services;

use App\Data\BulkRaiseInvoicesData;
use App\Data\CreateExpenseData;
use App\Data\CreateFeeStructureData;
use App\Data\RaiseInvoiceData;
use App\Data\RecordPaymentData;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Pupil;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    public function createFeeStructure(int $schoolId, CreateFeeStructureData $data): FeeStructure
    {
        return FeeStructure::create([
            'school_id' => $schoolId,
            'grade_id' => $data->grade_id,
            'term_id' => $data->term_id,
            'academic_year_id' => $data->academic_year_id,
            'name' => $data->name,
            'description' => $data->description,
            'amount' => $data->amount,
            'applies_to' => $data->applies_to,
            'is_mandatory' => $data->is_mandatory,
        ]);
    }

    public function raiseInvoice(int $schoolId, RaiseInvoiceData $data): FeeInvoice
    {
        $structure = FeeStructure::findOrFail($data->fee_structure_id);
        $balanceDue = $structure->amount - $data->discount;

        $pupil = Pupil::findOrFail($data->pupil_id);

        return FeeInvoice::create([
            'school_id' => $schoolId,
            'pupil_id' => $data->pupil_id,
            'fee_structure_id' => $data->fee_structure_id,
            'term_id' => $data->term_id,
            'academic_year_id' => $structure->academic_year_id,
            'amount' => $structure->amount,
            'discount' => $data->discount,
            'balance_due' => $balanceDue,
            'due_date' => $data->due_date,
            'status' => 'unpaid',
        ]);
    }

    public function bulkRaiseInvoices(int $schoolId, BulkRaiseInvoicesData $data): int
    {
        $structure = FeeStructure::findOrFail($data->fee_structure_id);

        $query = Pupil::where('school_id', $schoolId)->where('status', 'active');

        if ($data->grade_id) {
            $query->where('grade_id', $data->grade_id);
        }

        $count = 0;

        DB::transaction(function () use ($query, $structure, $data, $schoolId, &$count) {
            foreach ($query->get() as $pupil) {
                $exists = FeeInvoice::where('school_id', $schoolId)
                    ->where('pupil_id', $pupil->id)
                    ->where('fee_structure_id', $structure->id)
                    ->where('term_id', $data->term_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                FeeInvoice::create([
                    'school_id' => $schoolId,
                    'pupil_id' => $pupil->id,
                    'fee_structure_id' => $structure->id,
                    'term_id' => $data->term_id,
                    'academic_year_id' => $structure->academic_year_id,
                    'amount' => $structure->amount,
                    'discount' => 0,
                    'balance_due' => $structure->amount,
                    'status' => 'unpaid',
                ]);

                $count++;
            }
        });

        return $count;
    }

    public function recordPayment(RecordPaymentData $data, int $receivedBy): FeePayment
    {
        $invoice = FeeInvoice::findOrFail($data->invoice_id);

        $payment = FeePayment::create([
            'school_id' => $invoice->school_id,
            'pupil_id' => $invoice->pupil_id,
            'invoice_id' => $invoice->id,
            'amount' => $data->amount,
            'payment_method' => $data->payment_method,
            'reference' => $data->reference,
            'transaction_id' => $data->transaction_id,
            'mobile_money_provider' => $data->mobile_money_provider,
            'received_by' => $receivedBy,
            'payment_date' => $data->payment_date,
        ]);

        $totalPaid = (float) $invoice->payments()->sum('amount');
        $balanceDue = (float) $invoice->balance_due;

        if ($totalPaid >= $balanceDue) {
            $invoice->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partial']);
        }

        return $payment;
    }

    public function waiveInvoice(int $invoiceId, string $reason, int $waivedBy): FeeInvoice
    {
        $invoice = FeeInvoice::findOrFail($invoiceId);
        $invoice->update(['status' => 'waived']);

        return $invoice;
    }

    /** @return array{invoices: Collection, payments: Collection, total_due: float, total_paid: float, outstanding: float} */
    public function getPupilFeeStatement(int $pupilId, int $termId): array
    {
        $invoices = FeeInvoice::where('pupil_id', $pupilId)
            ->where('term_id', $termId)
            ->with('feeStructure:id,name', 'payments')
            ->get();

        $payments = FeePayment::where('pupil_id', $pupilId)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->get();

        $totalDue = $invoices->sum('balance_due');
        $totalPaid = $payments->sum('amount');

        return [
            'invoices' => $invoices,
            'payments' => $payments,
            'total_due' => (float) $totalDue,
            'total_paid' => (float) $totalPaid,
            'outstanding' => max(0.0, (float) $totalDue - (float) $totalPaid),
        ];
    }

    /** @return array{total_invoiced: float, total_collected: float, outstanding: float, collection_rate_pct: float, by_grade: array} */
    public function getSchoolFeeReport(int $schoolId, int $termId): array
    {
        $invoices = FeeInvoice::where('school_id', $schoolId)
            ->where('term_id', $termId)
            ->with('pupil.grade:id,grade_number', 'payments')
            ->get();

        $totalInvoiced = $invoices->sum('balance_due');
        $totalCollected = FeePayment::where('school_id', $schoolId)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->sum('amount');

        $outstanding = max(0.0, (float) $totalInvoiced - (float) $totalCollected);
        $collectionRatePct = $totalInvoiced > 0
            ? round(((float) $totalCollected / (float) $totalInvoiced) * 100, 1)
            : 0.0;

        $byGrade = $invoices->groupBy(fn ($i) => $i->pupil?->grade?->grade_number ?? 'Unknown')
            ->map(function ($group, $grade) {
                $gradeInvoiced = $group->sum('balance_due');
                $gradeCollected = $group->flatMap->payments->sum('amount');

                return [
                    'grade' => $grade,
                    'invoiced' => (float) $gradeInvoiced,
                    'collected' => (float) $gradeCollected,
                    'outstanding' => max(0.0, (float) $gradeInvoiced - (float) $gradeCollected),
                    'collection_pct' => $gradeInvoiced > 0
                        ? round(((float) $gradeCollected / (float) $gradeInvoiced) * 100, 1)
                        : 0.0,
                ];
            })
            ->values()
            ->toArray();

        return [
            'total_invoiced' => (float) $totalInvoiced,
            'total_collected' => (float) $totalCollected,
            'outstanding' => $outstanding,
            'collection_rate_pct' => $collectionRatePct,
            'by_grade' => $byGrade,
        ];
    }

    public function recordExpense(int $schoolId, CreateExpenseData $data, ?UploadedFile $receipt = null): Expense
    {
        $expense = Expense::create([
            'school_id' => $schoolId,
            'category' => $data->category,
            'description' => $data->description,
            'amount' => $data->amount,
            'expense_date' => $data->expense_date,
            'receipt_no' => $data->receipt_no,
        ]);

        if ($receipt) {
            $expense->addMedia($receipt)->toMediaCollection('receipts');
        }

        return $expense;
    }

    /** @return array{by_category: array<int, array{category: string, budget: float, actual: float, variance: float}>} */
    public function getBudgetVsActual(int $schoolId, int $academicYearId): array
    {
        $budgets = Budget::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->keyBy('category');

        $actuals = Expense::where('school_id', $schoolId)
            ->get()
            ->groupBy('category')
            ->map(fn ($group) => $group->sum('amount'));

        $categories = $budgets->keys()->merge($actuals->keys())->unique()->sort()->values();

        $byCategory = $categories->map(function ($category) use ($budgets, $actuals) {
            $budget = (float) ($budgets[$category]?->amount ?? 0);
            $actual = (float) ($actuals[$category] ?? 0);

            return [
                'category' => $category,
                'budget' => $budget,
                'actual' => $actual,
                'variance' => $budget - $actual,
            ];
        })->values()->toArray();

        return ['by_category' => $byCategory];
    }
}
