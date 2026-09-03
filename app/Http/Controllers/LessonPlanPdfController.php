<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LessonPlanPdfController extends Controller
{
    /**
     * The whole plan as a downloadable PDF. Rendering it server-side keeps every
     * copy identical — the browser's own "save as PDF" varies by machine — and
     * gives the file a name a teacher can hand in.
     */
    public function __invoke(Request $request, LessonPlan $lessonPlan): Response
    {
        $this->authorize('lesson-plan.view');

        abort_if($lessonPlan->school_id !== app('current_school')?->id, 403);

        $canReview = $request->user()->can('lesson-plan.approve');

        // Reviewers may read any plan in the school; everyone else only their own.
        abort_if(! $canReview && $lessonPlan->submitted_by !== $request->user()->id, 403);

        $lessonPlan->load([
            'subject:id,name',
            'stream:id,name,grade_id',
            'stream.grade:id,name',
            'term:id,name',
            'teacher:id,name',
            'reviewer:id,name',
            'reverter:id,name',
            'media',
        ]);

        $pdf = Pdf::loadView('pdf.lesson-plan', [
            'plan' => $lessonPlan,
            'school' => app('current_school'),
            'attachments' => $lessonPlan->getMedia(LessonPlan::ATTACHMENTS),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($this->filename($lessonPlan));
    }

    /** e.g. "lesson-plan-photosynthesis-2026-09-03.pdf". */
    private function filename(LessonPlan $plan): string
    {
        $parts = array_filter([
            'lesson-plan',
            str($plan->topic)->slug()->limit(60, '')->toString(),
            $plan->lesson_date?->format('Y-m-d'),
        ]);

        return implode('-', $parts).'.pdf';
    }
}
