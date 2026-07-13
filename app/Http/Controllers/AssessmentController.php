<?php

namespace App\Http\Controllers;

use App\Data\CreateAssessmentData;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Pupil;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $query = Assessment::where('school_id', $school->id)
            ->with(['stream:id,name', 'subject:id,name,code', 'term:id,name,number'])
            ->orderByDesc('date');

        if ($streamId = $request->integer('stream_id') ?: null) {
            $query->where('stream_id', $streamId);
        }
        if ($subjectId = $request->integer('subject_id') ?: null) {
            $query->where('subject_id', $subjectId);
        }
        if ($termId = $request->integer('term_id') ?: null) {
            $query->where('term_id', $termId);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        return Inertia::render('Assessments/Index', [
            'assessments' => $query->paginate(25)->withQueryString(),
            'streams' => Stream::where('school_id', $school->id)->with('grade:id,name')->get(['id', 'name', 'grade_id']),
            'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(['id', 'name', 'code']),
            'terms' => Term::where('school_id', $school->id)->orderBy('number')->get(['id', 'name', 'number']),
            'filters' => $request->only('stream_id', 'subject_id', 'term_id', 'type'),
        ]);
    }

    public function store(CreateAssessmentData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->authorize('assessment.create');

        $assessment = $this->resultsService->createAssessment($school->id, $data);

        return redirect()->route('assessments.index')
            ->with('success', 'Assessment created.');
    }

    public function show(Assessment $assessment): Response
    {
        abort_if($assessment->school_id !== app('current_school')?->id, 403);

        $assessment->load([
            'subject:id,name,code',
            'stream:id,name',
            'term:id,name,number',
            'createdBy:id,name',
            'scores.media',
        ]);

        $pupils = Pupil::where('stream_id', $assessment->stream_id)
            ->active()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        return Inertia::render('Assessments/Show', [
            'assessment'  => $assessment,
            'pupils'      => $pupils,
            'attachments' => $this->answerSheetsByPupil($assessment),
        ]);
    }

    /**
     * Answer sheets already on file, keyed by pupil so each score row can show its own.
     */
    private function answerSheetsByPupil(Assessment $assessment): array
    {
        return $assessment->scores->mapWithKeys(fn (AssessmentScore $score) => [
            $score->pupil_id => $score->getMedia(AssessmentScore::ANSWER_SHEETS)
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'score_id' => $score->id,
                    'name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
                    'url' => route('assessment-scores.attachments.show', [$score->id, $media->id]),
                ])
                ->values(),
        ])->filter(fn ($media) => $media->isNotEmpty())->all();
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        abort_if($assessment->school_id !== app('current_school')?->id, 403);

        $assessment->delete();

        return redirect()->route('assessments.index')->with('success', 'Assessment deleted.');
    }
}
