<?php

namespace App\Http\Controllers;

use App\Data\StoreLessonPlanData;
use App\Models\LessonPlan;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Services\LessonPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonPlanController extends Controller
{
    public function __construct(private readonly LessonPlanService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('lesson-plan.view');

        $school = app('current_school');
        $canReview = $request->user()->can('lesson-plan.approve');

        $status = $request->string('status')->toString() ?: ($canReview ? 'submitted' : 'all');

        $query = LessonPlan::where('school_id', $school->id)
            ->with([
                'subject:id,name',
                'stream:id,name',
                'term:id,name',
                'submittedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->withCount('media')
            ->orderByDesc('created_at');

        // Reviewers see every teacher's plans; authors only see their own.
        if (! $canReview) {
            $query->forUser($request->user()->id);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($subjectId = $request->integer('subject_id')) {
            $query->where('subject_id', $subjectId);
        }

        if ($streamId = $request->integer('stream_id')) {
            $query->where('stream_id', $streamId);
        }

        return Inertia::render('LessonPlans/Index', [
            'lessonPlans' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'status' => $status,
                'subject_id' => $subjectId ?? null,
                'stream_id' => $streamId ?? null,
            ],
            'canReview' => $canReview,
            'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']),
            'streams' => Stream::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('lesson-plan.create');

        return Inertia::render('LessonPlans/Create', $this->formOptions());
    }

    public function store(StoreLessonPlanData $data): RedirectResponse
    {
        $this->authorize('lesson-plan.create');

        $school = app('current_school');

        $this->service->create($school->id, auth()->id(), $data);

        return redirect()->route('lesson-plans.index')
            ->with('success', $data->submit ? 'Lesson plan submitted for approval.' : 'Lesson plan saved as draft.');
    }

    public function edit(LessonPlan $lessonPlan): Response
    {
        $this->authorize('lesson-plan.update');
        $this->authorizeSchool($lessonPlan);
        $this->authorizeEditable($lessonPlan);

        $lessonPlan->load('media');

        return Inertia::render('LessonPlans/Edit', array_merge($this->formOptions(), [
            'lessonPlan' => array_merge($lessonPlan->toArray(), [
                'attachments' => $lessonPlan->getMedia(LessonPlan::ATTACHMENTS)->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->file_name,
                    'url' => route('lesson-plans.attachments.show', [$lessonPlan->id, $m->id]),
                ]),
            ]),
        ]));
    }

    public function update(StoreLessonPlanData $data, LessonPlan $lessonPlan): RedirectResponse
    {
        $this->authorize('lesson-plan.update');
        $this->authorizeSchool($lessonPlan);
        $this->authorizeEditable($lessonPlan);

        $this->service->update($lessonPlan, $data);

        return redirect()->route('lesson-plans.index')
            ->with('success', $data->submit ? 'Lesson plan submitted for approval.' : 'Lesson plan updated.');
    }

    public function destroy(LessonPlan $lessonPlan): RedirectResponse
    {
        $this->authorize('lesson-plan.delete');
        $this->authorizeSchool($lessonPlan);
        $this->authorizeEditable($lessonPlan);

        $lessonPlan->delete();

        return back()->with('success', 'Lesson plan deleted.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        $school = app('current_school');

        return [
            'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']),
            'streams' => Stream::where('school_id', $school->id)->with('grade:id,name')->orderBy('name')->get(['id', 'name', 'grade_id']),
            'terms' => Term::where('school_id', $school->id)->orderBy('number')->get(['id', 'name', 'is_current']),
        ];
    }

    private function authorizeSchool(LessonPlan $lessonPlan): void
    {
        abort_if($lessonPlan->school_id !== app('current_school')?->id, 403);
    }

    /**
     * Only the author may edit, and only while the plan is a draft or has been
     * rejected. Approved and pending-review plans are locked.
     */
    private function authorizeEditable(LessonPlan $lessonPlan): void
    {
        abort_if($lessonPlan->submitted_by !== auth()->id(), 403);
        abort_if(! in_array($lessonPlan->status, ['draft', 'rejected'], true), 403,
            'This lesson plan can no longer be edited.');
    }
}
