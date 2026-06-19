<?php

namespace App\Http\Controllers;

use App\Data\BulkEnterTermResultsData;
use App\Models\Pupil;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermResultController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $streamId = $request->integer('stream_id') ?: null;
        $termId = $request->integer('term_id') ?: null;
        $subjectId = $request->integer('subject_id') ?: null;

        $results = ($streamId && $termId)
            ? TermResult::where('stream_id', $streamId)
                ->where('term_id', $termId)
                ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
                ->with(['pupil:id,first_name,last_name,admission_no', 'subject:id,name,code'])
                ->orderBy('subject_id')
                ->get()
            : null;

        $pupils = $streamId
            ? Pupil::where('stream_id', $streamId)->where('status', 'active')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no'])
            : collect();

        return Inertia::render('Results/TermResults', [
            'streams' => Stream::where('school_id', $school->id)->with('grade:id,name')->get(['id', 'name', 'grade_id']),
            'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(['id', 'name', 'code']),
            'terms' => Term::where('school_id', $school->id)->orderBy('number')->get(['id', 'name', 'number', 'is_current']),
            'results' => $results,
            'pupils' => $pupils,
            'filters' => $request->only('stream_id', 'term_id', 'subject_id'),
        ]);
    }

    public function store(BulkEnterTermResultsData $data): RedirectResponse
    {
        $this->resultsService->enterTermResults($data, auth()->id());

        return back()->with('success', 'Results saved successfully.');
    }
}
