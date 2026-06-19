<?php

namespace App\Http\Controllers;

use App\Data\CreateDormitoryData;
use App\Models\Dormitory;
use App\Models\Term;
use App\Services\BoardingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DormitoryController extends Controller
{
    public function __construct(private readonly BoardingService $boardingService) {}

    public function index(): Response
    {
        $school = app('current_school');

        $dormitories = $this->boardingService->getDormitoryOccupancy($school->id);

        return Inertia::render('Boarding/Dormitories/Index', [
            'dormitories' => $dormitories,
        ]);
    }

    public function store(CreateDormitoryData $data): RedirectResponse
    {
        $school = app('current_school');
        $dorm = $this->boardingService->createDormitory($school->id, $data);

        return redirect()->route('dormitories.show', $dorm->id)
            ->with('success', 'Dormitory created.');
    }

    public function show(Dormitory $dormitory): Response
    {
        abort_if($dormitory->school_id !== app('current_school')?->id, 403);

        $dormitory->load([
            'beds' => fn ($q) => $q->orderBy('bed_number'),
            'beds.activeAllocation.pupil:id,first_name,last_name,admission_no',
        ]);

        $termId = request()->integer('term_id');
        $terms = Term::where('school_id', $dormitory->school_id)->orderByDesc('id')->get(['id', 'name']);

        return Inertia::render('Boarding/Dormitories/Show', [
            'dormitory' => $dormitory,
            'terms' => $terms,
            'term_id' => $termId ?: null,
        ]);
    }

    public function destroy(Dormitory $dormitory): RedirectResponse
    {
        abort_if($dormitory->school_id !== app('current_school')?->id, 403);
        $dormitory->delete();

        return redirect()->route('dormitories.index')->with('success', 'Dormitory deleted.');
    }
}
