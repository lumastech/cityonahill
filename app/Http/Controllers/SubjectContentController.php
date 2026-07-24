<?php

namespace App\Http\Controllers;

use App\Data\StoreSubjectContentData;
use App\Models\Subject;
use App\Models\SubjectLearningContent;
use Illuminate\Http\RedirectResponse;

class SubjectContentController extends Controller
{
    public function store(StoreSubjectContentData $data, Subject $subject): RedirectResponse
    {
        $this->authorize('subject.update');
        $this->authorizeSchool($subject->school_id);

        $content = SubjectLearningContent::create([
            'school_id' => $subject->school_id,
            'subject_id' => $subject->id,
            'grade_id' => $data->grade_id,
            'title' => $data->title,
            'body' => $data->body,
            'sort_order' => $data->sort_order,
        ]);

        $this->attachFiles($content, $data);

        return back()->with('success', 'Learning content added.');
    }

    public function update(StoreSubjectContentData $data, Subject $subject, SubjectLearningContent $content): RedirectResponse
    {
        $this->authorize('subject.update');
        $this->authorizeSchool($subject->school_id);
        $this->guardContent($subject, $content);

        $content->update([
            'grade_id' => $data->grade_id,
            'title' => $data->title,
            'body' => $data->body,
            'sort_order' => $data->sort_order,
        ]);

        $this->attachFiles($content, $data);

        return back()->with('success', 'Learning content updated.');
    }

    public function destroy(Subject $subject, SubjectLearningContent $content): RedirectResponse
    {
        $this->authorize('subject.update');
        $this->authorizeSchool($subject->school_id);
        $this->guardContent($subject, $content);

        $content->delete();

        return back()->with('success', 'Learning content deleted.');
    }

    private function attachFiles(SubjectLearningContent $content, StoreSubjectContentData $data): void
    {
        foreach (array_filter($data->files) as $file) {
            $content->addMedia($file)->toMediaCollection(SubjectLearningContent::MEDIA);
        }
    }

    private function authorizeSchool(int $schoolId): void
    {
        abort_if($schoolId !== app('current_school')?->id, 403);
    }

    private function guardContent(Subject $subject, SubjectLearningContent $content): void
    {
        abort_if($content->subject_id !== $subject->id, 404);
    }
}
