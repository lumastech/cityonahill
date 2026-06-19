<?php

namespace App\Http\Controllers;

use App\Data\CreateFeeStructureData;
use App\Models\FeeStructure;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeeStructureController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function index(): Response
    {
        $school = app('current_school');

        $feeStructures = FeeStructure::where('school_id', $school->id)
            ->with('grade:id,grade_number', 'term:id,name', 'academicYear:id,name')
            ->orderBy('term_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/FeeStructures/Index', [
            'fee_structures' => $feeStructures,
        ]);
    }

    public function store(CreateFeeStructureData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->financeService->createFeeStructure($school->id, $data);

        return back()->with('success', 'Fee structure created.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        abort_if($feeStructure->school_id !== app('current_school')?->id, 403);

        $feeStructure->delete();

        return back()->with('success', 'Fee structure deleted.');
    }
}
