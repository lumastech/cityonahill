<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonPlanAttachmentController extends Controller
{
    public function show(LessonPlan $lessonPlan, Media $media): BinaryFileResponse
    {
        $this->authorize('lesson-plan.view');

        $this->guardAttachment($lessonPlan, $media);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }

    public function destroy(LessonPlan $lessonPlan, Media $media): RedirectResponse
    {
        $this->authorize('lesson-plan.update');

        $this->guardAttachment($lessonPlan, $media);

        abort_if($lessonPlan->submitted_by !== auth()->id(), 403);
        abort_if(! in_array($lessonPlan->status, ['draft', 'rejected'], true), 403);

        $media->delete();

        return back()->with('success', 'Attachment removed.');
    }

    /**
     * The media must belong to this lesson plan's attachment collection, and the
     * plan must belong to the school the user is currently viewing.
     */
    private function guardAttachment(LessonPlan $lessonPlan, Media $media): void
    {
        abort_if($lessonPlan->school_id !== app('current_school')?->id, 403);

        abort_if(
            $media->model_type !== $lessonPlan->getMorphClass()
                || (int) $media->model_id !== $lessonPlan->id
                || $media->collection_name !== LessonPlan::ATTACHMENTS,
            404
        );
    }
}
