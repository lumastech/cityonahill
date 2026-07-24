<?php

namespace App\Http\Controllers;

use App\Data\ReviewLessonPlanData;
use App\Models\LessonPlan;
use App\Services\LessonPlanService;
use Illuminate\Http\RedirectResponse;

class ReviewLessonPlanController extends Controller
{
    public function __construct(private readonly LessonPlanService $service) {}

    public function __invoke(ReviewLessonPlanData $data, LessonPlan $lessonPlan): RedirectResponse
    {
        abort_if($lessonPlan->school_id !== app('current_school')?->id, 403);

        $this->authorize('lesson-plan.approve');

        abort_if($lessonPlan->status !== 'submitted', 422,
            'Only submitted lesson plans can be reviewed.');

        $this->service->review($lessonPlan, $data, auth()->id());

        return back()->with('success', 'Lesson plan '.$data->status.'.');
    }
}
