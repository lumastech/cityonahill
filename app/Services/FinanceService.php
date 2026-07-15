<?php

namespace App\Services;

use App\Data\BulkRaiseInvoicesData;
use App\Data\CreateExpenseData;
use App\Data\CreateFeeStructureData;
use App\Data\CreateOtherIncomeData;
use App\Data\RaiseInvoiceData;
use App\Data\RecordPaymentData;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\OtherIncome;
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
                    'due_date' => $data->due_date,
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

        $this->syncInvoiceStatus($invoice);

        return $payment;
    }

    public function reconcileInvoice(FeeInvoice $invoice): void
    {
        $this->syncInvoiceStatus($invoice);
    }

    /**
     * Recompute an invoice's status from its completed payments.
     *
     * `balance_due` is the immutable net amount billed (amount − discount) and is
     * never decremented here; the amount still owed is derived as balance_due − amount_paid.
     */
    private function syncInvoiceStatus(FeeInvoice $invoice): void
    {
        if ($invoice->status === 'waived') {
            return;
        }

        $netBilled = (float) $invoice->balance_due;
        $totalPaid = $invoice->amount_paid;

        if ($netBilled > 0 && $totalPaid >= $netBilled) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        if ($invoice->status !== $status) {
            $invoice->update(['status' => $status]);
        }
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

    /**
     * Accounts-receivable aging as of a given date. Outstanding invoices (unpaid/partial) are
     * bucketed by how long they have been overdue relative to $asOf, using balance_due − amount_paid.
     *
     * @return array{
     *     as_of: string,
     *     buckets: array<int, array{key: string, label: string, amount: float, count: int}>,
     *     total_outstanding: float,
     *     total_count: int,
     *     debtors: array<int, array{pupil_id: int, name: string, admission_no: string, grade: string|int, outstanding: float, invoice_count: int, oldest_due_date: ?string}>
     * }
     */
    public function getReceivablesAging(int $schoolId, ?string $asOf = null): array
    {
        $asOfDate = $asOf ? \Carbon\Carbon::parse($asOf)->startOfDay() : now()->startOfDay();

        $invoices = FeeInvoice::where('school_id', $schoolId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('pupil:id,first_name,other_name,last_name,admission_no,grade_id', 'pupil.grade:id,grade_number', 'payments')
            ->get();

        $buckets = [
            'current' => ['key' => 'current', 'label' => 'Not yet due', 'amount' => 0.0, 'count' => 0],
            '1_30' => ['key' => '1_30', 'label' => '1–30 days', 'amount' => 0.0, 'count' => 0],
            '31_60' => ['key' => '31_60', 'label' => '31–60 days', 'amount' => 0.0, 'count' => 0],
            '61_90' => ['key' => '61_90', 'label' => '61–90 days', 'amount' => 0.0, 'count' => 0],
            '90_plus' => ['key' => '90_plus', 'label' => '90+ days', 'amount' => 0.0, 'count' => 0],
        ];

        $debtors = [];
        $totalOutstanding = 0.0;
        $totalCount = 0;

        foreach ($invoices as $invoice) {
            $outstanding = max(0.0, (float) $invoice->balance_due - $invoice->amount_paid);

            if ($outstanding <= 0) {
                continue;
            }

            $bucketKey = $this->agingBucket($invoice->due_date, $asOfDate);
            $buckets[$bucketKey]['amount'] += $outstanding;
            $buckets[$bucketKey]['count']++;

            $totalOutstanding += $outstanding;
            $totalCount++;

            $pupil = $invoice->pupil;
            $pid = $invoice->pupil_id;

            if (! isset($debtors[$pid])) {
                $debtors[$pid] = [
                    'pupil_id' => $pid,
                    'name' => $pupil?->full_name ?? 'Unknown',
                    'admission_no' => $pupil?->admission_no ?? '',
                    'grade' => $pupil?->grade?->grade_number ?? '—',
                    'outstanding' => 0.0,
                    'invoice_count' => 0,
                    'oldest_due_date' => null,
                ];
            }

            $debtors[$pid]['outstanding'] += $outstanding;
            $debtors[$pid]['invoice_count']++;

            $due = $invoice->due_date?->toDateString();
            if ($due && (! $debtors[$pid]['oldest_due_date'] || $due < $debtors[$pid]['oldest_due_date'])) {
                $debtors[$pid]['oldest_due_date'] = $due;
            }
        }

        usort($debtors, fn ($a, $b) => $b['outstanding'] <=> $a['outstanding']);

        return [
            'as_of' => $asOfDate->toDateString(),
            'buckets' => array_values($buckets),
            'total_outstanding' => $totalOutstanding,
            'total_count' => $totalCount,
            'debtors' => array_values($debtors),
        ];
    }

    private function agingBucket(?\Carbon\Carbon $dueDate, \Carbon\Carbon $asOf): string
    {
        if (! $dueDate || $dueDate->startOfDay()->greaterThanOrEqualTo($asOf)) {
            return 'current';
        }

        $daysOverdue = $dueDate->startOfDay()->diffInDays($asOf);

        return match (true) {
            $daysOverdue <= 30 => '1_30',
            $daysOverdue <= 60 => '31_60',
            $daysOverdue <= 90 => '61_90',
            default => '90_plus',
        };
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

    /**
     * Monthly income (fees + other) vs expenses for the trailing $months months, oldest first.
     *
     * @return array<int, array{month: string, label: string, income: float, expenses: float}>
     */
    public function getMonthlyIncomeExpense(int $schoolId, int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $series = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo(now()); $cursor->addMonth()) {
            $series[$cursor->format('Y-m')] = [
                'month' => $cursor->format('Y-m'),
                'label' => $cursor->format('M'),
                'income' => 0.0,
                'expenses' => 0.0,
            ];
        }

        $fees = FeePayment::where('school_id', $schoolId)
            ->where('payment_date', '>=', $start->toDateString())
            ->where(function ($q) {
                $q->whereNull('gateway_status')->orWhere('gateway_status', 'completed');
            })
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $other = OtherIncome::where('school_id', $schoolId)
            ->where('received_date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(received_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $expenses = Expense::where('school_id', $schoolId)
            ->where('expense_date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        foreach ($series as $ym => &$row) {
            $row['income'] = (float) ($fees[$ym] ?? 0) + (float) ($other[$ym] ?? 0);
            $row['expenses'] = (float) ($expenses[$ym] ?? 0);
        }

        return array_values($series);
    }

    /**
     * Cash-basis profit & loss for a date range. Income is fee payments actually received plus
     * other income, less expenses paid, all keyed on their transaction dates within [$from, $to].
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     fees_collected: float,
     *     other_income_total: float,
     *     other_income_by_source: array<int, array{source: string, amount: float}>,
     *     total_income: float,
     *     expenses_by_category: array<int, array{category: string, amount: float}>,
     *     total_expenses: float,
     *     net: float
     * }
     */
    public function getProfitAndLoss(int $schoolId, string $from, string $to): array
    {
        $feesCollected = (float) FeePayment::where('school_id', $schoolId)
            ->whereBetween('payment_date', [$from, $to])
            ->where(function ($q) {
                $q->whereNull('gateway_status')->orWhere('gateway_status', 'completed');
            })
            ->sum('amount');

        $otherBySource = OtherIncome::where('school_id', $schoolId)
            ->whereBetween('received_date', [$from, $to])
            ->selectRaw('source, SUM(amount) as amount')
            ->groupBy('source')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => ['source' => $row->source, 'amount' => (float) $row->amount])
            ->all();

        $otherIncomeTotal = array_sum(array_column($otherBySource, 'amount'));
        $totalIncome = $feesCollected + $otherIncomeTotal;

        $expensesByCategory = Expense::where('school_id', $schoolId)
            ->whereBetween('expense_date', [$from, $to])
            ->selectRaw('category, SUM(amount) as amount')
            ->groupBy('category')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => ['category' => $row->category, 'amount' => (float) $row->amount])
            ->all();

        $totalExpenses = array_sum(array_column($expensesByCategory, 'amount'));

        return [
            'from' => $from,
            'to' => $to,
            'fees_collected' => $feesCollected,
            'other_income_total' => $otherIncomeTotal,
            'other_income_by_source' => $otherBySource,
            'total_income' => $totalIncome,
            'expenses_by_category' => $expensesByCategory,
            'total_expenses' => $totalExpenses,
            'net' => $totalIncome - $totalExpenses,
        ];
    }

    public function recordOtherIncome(int $schoolId, CreateOtherIncomeData $data, int $recordedBy, ?UploadedFile $receipt = null): OtherIncome
    {
        $income = OtherIncome::create([
            'school_id' => $schoolId,
            'source' => $data->source,
            'description' => $data->description,
            'amount' => $data->amount,
            'received_date' => $data->received_date,
            'recorded_by' => $recordedBy,
            'reference' => $data->reference,
        ]);

        if ($receipt) {
            $income->addMedia($receipt)->toMediaCollection('receipts');
        }

        return $income;
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
