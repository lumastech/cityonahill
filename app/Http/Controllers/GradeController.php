<?php

namespace App\Http\Controllers;

use App\Data\StoreGradeData;
use App\Models\Grade;
use App\Models\Subject;
use App\Services\ClassStructureService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function __construct(private readonly ClassStructureService $service) {}

    public function index(): Response
    {
        $school = app('current_school');

        $grades = Grade::forSchool($school->id)
            ->orderBy('order_index')
            ->withCount('streams', 'pupils')
            ->get();

        return Inertia::render('Grades/Index', [
            'grades' => $grades,
        ]);
    }

    public function edit(Grade $grade): Response
    {
        $this->authorizeSchool($grade);

        $grade->load('gradeSubjects.subject');

        $linkedIds = $grade->gradeSubjects->pluck('subject_id');

        return Inertia::render('Grades/Edit', [
            'grade'             => $grade,
            'available_subjects' => Subject::where('school_id', $grade->school_id)
                ->whereNotIn('id', $linkedIds)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreGradeData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->service->createGrade($school->id, $data);

        return redirect()->route('grades.index')
            ->with('success', 'Grade created successfully.');
    }

    public function update(StoreGradeData $data, Grade $grade): RedirectResponse
    {
        $this->authorizeSchool($grade);

        $this->service->updateGrade($grade, $data);

        return redirect()->route('grades.index')
            ->with('success', 'Grade updated successfully.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $this->authorizeSchool($grade);

        $grade->delete();

        return redirect()->route('grades.index')
            ->with('success', 'Grade deleted.');
    }

    private function authorizeSchool(Grade $grade): void
    {
        $school = app('current_school');

        abort_if($grade->school_id !== $school?->id, 403);
    }
}
