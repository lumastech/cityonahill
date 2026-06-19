<?php

namespace App\Http\Controllers;

use App\Data\CreateExpenseData;
use App\Models\Expense;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');
        $category = $request->string('category')->toString() ?: null;

        $query = Expense::where('school_id', $school->id)
            ->with('approvedBy:id,name')
            ->orderByDesc('expense_date');

        if ($category) {
            $query->where('category', $category);
        }

        return Inertia::render('Finance/Expenses/Index', [
            'expenses' => $query->paginate(30)->withQueryString(),
            'filters' => compact('category'),
        ]);
    }

    public function store(CreateExpenseData $data, Request $request): RedirectResponse
    {
        $school = app('current_school');
        $receipt = $request->file('receipt');

        $this->financeService->recordExpense($school->id, $data, $receipt);

        return back()->with('success', 'Expense recorded.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        abort_if($expense->school_id !== app('current_school')?->id, 403);

        $expense->delete();

        return back()->with('success', 'Expense deleted.');
    }
}
