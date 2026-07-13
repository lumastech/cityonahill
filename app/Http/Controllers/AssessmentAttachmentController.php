<?php

namespace App\Http\Controllers;

use App\Models\AssessmentScore;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssessmentAttachmentController extends Controller
{
    public function show(AssessmentScore $score, Media $media): BinaryFileResponse
    {
        $this->authorize('assessment.view');

        $this->guardAttachment($score, $media);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }

    public function destroy(AssessmentScore $score, Media $media): RedirectResponse
    {
        $this->authorize('assessment.update');

        $this->guardAttachment($score, $media);

        $media->delete();

        return back()->with('success', 'Answer sheet removed.');
    }

    /**
     * The media must be an answer sheet belonging to this score, and the score
     * must belong to an assessment in the school the user is currently viewing.
     */
    private function guardAttachment(AssessmentScore $score, Media $media): void
    {
        abort_if($score->assessment?->school_id !== app('current_school')?->id, 403);

        abort_if(
            $media->model_type !== $score->getMorphClass()
                || (int) $media->model_id !== $score->id
                || $media->collection_name !== AssessmentScore::ANSWER_SHEETS,
            404
        );
    }
}
