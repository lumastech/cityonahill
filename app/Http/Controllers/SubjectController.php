<?php

namespace App\Http\Controllers;

use App\Data\StoreSubjectData;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\SubjectLearningContent;
use App\Services\ClassStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(private readonly ClassStructureService $service) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $subjects = Subject::where('school_id', $school->id)
            ->when($request->input('category'), fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->boolean('is_ecz_subject'), fn ($q) => $q->ecz())
            ->orderBy('name')
            ->get();

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
            'filterCategory' => $request->input('category'),
        ]);
    }

    public function edit(Subject $subject): Response
    {
        $this->authorizeSchool($subject);

        $school = app('current_school');

        $subject->load(['learningContents.media']);

        return Inertia::render('Subjects/Edit', [
            'subject' => $subject,
            'contents' => $subject->learningContents->map(fn ($content) => [
                'id' => $content->id,
                'title' => $content->title,
                'body' => $content->body,
                'grade_id' => $content->grade_id,
                'sort_order' => $content->sort_order,
                'media' => $content->getMedia(SubjectLearningContent::MEDIA)->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->file_name,
                    'url' => route('subject-contents.media.show', [$content->id, $m->id]),
                ]),
            ]),
            'grades' => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name']),
        ]);
    }

    public function store(StoreSubjectData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->service->createSubject($school->id, $data);

        return redirect()->route('subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function update(StoreSubjectData $data, Subject $subject): RedirectResponse
    {
        $this->authorizeSchool($subject);

        $subject->update([
            'name' => $data->name,
            'code' => $data->code,
            'category' => $data->category,
            'is_zambian_language' => $data->is_zambian_language,
            'is_ecz_subject' => $data->is_ecz_subject,
            'description' => $data->description,
        ]);

        return redirect()->route('subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorizeSchool($subject);

        $subject->delete();

        return redirect()->route('subjects.index')
            ->with('success', 'Subject deleted.');
    }

    private function authorizeSchool(Subject $subject): void
    {
        $school = app('current_school');

        abort_if($subject->school_id !== $school?->id, 403);
    }
}
