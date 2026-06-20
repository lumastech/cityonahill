<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\GradeSubject;
use App\Services\ClassStructureService;
use Illuminate\Http\RedirectResponse;

class UnlinkSubjectController extends Controller
{
    public function __construct(private readonly ClassStructureService $service) {}

    public function __invoke(Grade $grade, GradeSubject $gradeSubject): RedirectResponse
    {
        $school = app('current_school');

        abort_if($grade->school_id !== $school?->id, 403);
        abort_if($gradeSubject->grade_id !== $grade->id, 403);

        $this->service->unlinkSubjectFromGrade($gradeSubject->id);

        return redirect()->route('grades.edit', $grade)
            ->with('success', 'Subject removed from grade.');
    }
}
