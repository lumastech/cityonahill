<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeeReportController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');
        $termId = $request->integer('term_id') ?: null;

        $report = $termId
            ? $this->financeService->getSchoolFeeReport($school->id, $termId)
            : null;

        return Inertia::render('Finance/Reports/Index', [
            'report' => $report,
            'terms' => Term::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']),
            'filters' => compact('termId'),
        ]);
    }
}
