<?php

namespace App\Http\Controllers;

use App\Models\SubjectLearningContent;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubjectContentAttachmentController extends Controller
{
    public function show(SubjectLearningContent $content, Media $media): BinaryFileResponse
    {
        $this->authorize('subject.view');

        $this->guardMedia($content, $media);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }

    public function destroy(SubjectLearningContent $content, Media $media): RedirectResponse
    {
        $this->authorize('subject.update');

        $this->guardMedia($content, $media);

        $media->delete();

        return back()->with('success', 'File removed.');
    }

    /**
     * The media must belong to this learning content's collection, and the content
     * must belong to the school the user is currently viewing.
     */
    private function guardMedia(SubjectLearningContent $content, Media $media): void
    {
        abort_if($content->school_id !== app('current_school')?->id, 403);

        abort_if(
            $media->model_type !== $content->getMorphClass()
                || (int) $media->model_id !== $content->id
                || $media->collection_name !== SubjectLearningContent::MEDIA,
            404
        );
    }
}
