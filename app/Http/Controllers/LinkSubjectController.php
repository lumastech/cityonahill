<?php

namespace App\Http\Controllers;

use App\Data\LinkSubjectToGradeData;
use App\Models\Grade;
use App\Services\ClassStructureService;
use Illuminate\Http\RedirectResponse;

class LinkSubjectController extends Controller
{
    public function __construct(private readonly ClassStructureService $service) {}

    public function __invoke(LinkSubjectToGradeData $data, Grade $grade): RedirectResponse
    {
        $school = app('current_school');

        abort_if($grade->school_id !== $school?->id, 403);

        $this->service->linkSubjectToGrade($school->id, $data);

        return redirect()->route('grades.edit', $grade)
            ->with('success', 'Subject linked to grade successfully.');
    }
}
