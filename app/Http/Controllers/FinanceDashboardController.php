<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceDashboardController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');

        $term = Term::where('school_id', $school->id)->current()->first();

        [$from, $to] = ($term && $term->start_date && $term->end_date)
            ? [$term->start_date->toDateString(), $term->end_date->toDateString()]
            : [now()->startOfYear()->toDateString(), now()->toDateString()];

        $pl = $this->financeService->getProfitAndLoss($school->id, $from, $to);
        $aging = $this->financeService->getReceivablesAging($school->id);

        return Inertia::render('Finance/Dashboard/Index', [
            'period' => [
                'label' => $term?->name ?? 'Year to date',
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'total_income' => $pl['total_income'],
                'fees_collected' => $pl['fees_collected'],
                'other_income' => $pl['other_income_total'],
                'total_expenses' => $pl['total_expenses'],
                'net' => $pl['net'],
                'outstanding' => $aging['total_outstanding'],
                'debtor_count' => count($aging['debtors']),
            ],
            'top_debtors' => array_slice($aging['debtors'], 0, 5),
            'aging_buckets' => $aging['buckets'],
            'trend' => $this->financeService->getMonthlyIncomeExpense($school->id, 6),
        ]);
    }
}
