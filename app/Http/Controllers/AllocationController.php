<?php

namespace App\Http\Controllers;

use App\Data\AllocateBedData;
use App\Data\VacateBedData;
use App\Models\BoardingAllocation;
use App\Models\Term;
use App\Services\BoardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllocationController extends Controller
{
    public function __construct(private readonly BoardingService $boardingService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');
        $termId = $request->integer('term_id');

        $terms = Term::where('school_id', $school->id)->orderByDesc('id')->get(['id', 'name']);
        $roster = $termId ? $this->boardingService->getTermRoster($school->id, $termId) : collect();

        return Inertia::render('Boarding/Roster', [
            'terms' => $terms,
            'term_id' => $termId ?: null,
            'roster' => $roster,
        ]);
    }

    public function store(AllocateBedData $data): RedirectResponse
    {
        $school = app('current_school');
        $this->boardingService->allocateBed($school->id, $data);

        return back()->with('success', 'Bed allocated successfully.');
    }

    public function vacate(VacateBedData $data, BoardingAllocation $allocation): RedirectResponse
    {
        abort_if($allocation->school_id !== app('current_school')?->id, 403);
        $this->boardingService->vacateBed($allocation->id, $data);

        return back()->with('success', 'Bed vacated.');
    }
}
