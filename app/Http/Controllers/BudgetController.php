<?php

namespace App\Http\Controllers;

use App\Data\CreateBudgetData;
use App\Models\AcademicYear;
use App\Models\Budget;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');
        $academicYearId = $request->integer('academic_year_id') ?: null;

        $query = Budget::where('school_id', $school->id)
            ->with('academicYear:id,year', 'term:id,name');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $budgetVsActual = $academicYearId
            ? $this->financeService->getBudgetVsActual($school->id, $academicYearId)
            : null;

        return Inertia::render('Finance/Budget/Index', [
            'budgets' => $query->orderBy('category')->get(),
            'academic_years' => AcademicYear::where('school_id', $school->id)->orderByDesc('name')->get(['id', 'name']),
            'budget_vs_actual' => $budgetVsActual,
            'filters' => compact('academicYearId'),
        ]);
    }

    public function store(CreateBudgetData $data): RedirectResponse
    {
        $school = app('current_school');

        Budget::updateOrCreate(
            [
                'school_id' => $school->id,
                'academic_year_id' => $data->academic_year_id,
                'term_id' => $data->term_id,
                'category' => $data->category,
            ],
            ['amount' => $data->amount]
        );

        return back()->with('success', 'Budget saved.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        abort_if($budget->school_id !== app('current_school')?->id, 403);

        $budget->delete();

        return back()->with('success', 'Budget entry deleted.');
    }
}
