<?php

namespace App\Http\Controllers;

use App\Data\RevertLessonPlanData;
use App\Models\LessonPlan;
use App\Services\LessonPlanService;
use Illuminate\Http\RedirectResponse;

class RevertLessonPlanController extends Controller
{
    public function __construct(private readonly LessonPlanService $service) {}

    public function __invoke(RevertLessonPlanData $data, LessonPlan $lessonPlan): RedirectResponse
    {
        abort_if($lessonPlan->school_id !== app('current_school')?->id, 403);

        $this->authorize('lesson-plan.approve');

        abort_if(! $lessonPlan->isRevertable(), 422,
            'Only an approved or rejected lesson plan can be reverted.');

        $this->service->revert($lessonPlan, $data, auth()->id());

        return back()->with('success', 'Lesson plan returned to the teacher.');
    }
}
