<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfitLossController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');

        $terms = Term::where('school_id', $school->id)
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'start_date', 'end_date']);

        [$from, $to] = $this->resolvePeriod($request, $terms);

        return Inertia::render('Finance/ProfitLoss/Index', [
            'statement' => $this->financeService->getProfitAndLoss($school->id, $from, $to),
            'terms' => $terms,
            'filters' => compact('from', 'to'),
        ]);
    }

    /** @return array{0: string, 1: string} */
    private function resolvePeriod(Request $request, \Illuminate\Support\Collection $terms): array
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        if ($from && $to) {
            return [$from, $to];
        }

        // Default to the current term when one is set, otherwise the calendar year to date.
        $current = $terms->firstWhere('id', Term::where('school_id', app('current_school')->id)->current()->value('id'));

        if ($current && $current->start_date && $current->end_date) {
            return [$current->start_date->toDateString(), $current->end_date->toDateString()];
        }

        return [now()->startOfYear()->toDateString(), now()->toDateString()];
    }
}
