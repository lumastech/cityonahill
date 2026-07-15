<?php

namespace App\Http\Controllers;

use App\Data\CreateOtherIncomeData;
use App\Models\OtherIncome;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OtherIncomeController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');
        $source = $request->string('source')->toString() ?: null;

        $query = OtherIncome::where('school_id', $school->id)
            ->with('media')
            ->orderByDesc('received_date');

        if ($source) {
            $query->where('source', $source);
        }

        return Inertia::render('Finance/OtherIncome/Index', [
            'incomes' => $query->paginate(30)->withQueryString(),
            'filters' => compact('source'),
        ]);
    }

    public function store(CreateOtherIncomeData $data, Request $request): RedirectResponse
    {
        $school = app('current_school');

        $this->financeService->recordOtherIncome(
            $school->id,
            $data,
            $request->user()->id,
            $request->file('receipt'),
        );

        return back()->with('success', 'Income recorded.');
    }

    public function destroy(OtherIncome $otherIncome): RedirectResponse
    {
        abort_if($otherIncome->school_id !== app('current_school')?->id, 403);

        $otherIncome->delete();

        return back()->with('success', 'Income deleted.');
    }
}
