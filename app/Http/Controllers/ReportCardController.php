<?php

namespace App\Http\Controllers;

use App\Data\AddReportCommentData;
use App\Data\GenerateReportCardData;
use App\Models\ReportCard;
use App\Models\Stream;
use App\Models\Term;
use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportCardController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $streamId = $request->integer('stream_id') ?: null;
        $termId = $request->integer('term_id') ?: null;

        $cards = ($streamId && $termId)
            ? ReportCard::where('school_id', $school->id)
                ->where('stream_id', $streamId)
                ->where('term_id', $termId)
                ->with(['pupil:id,first_name,last_name,admission_no'])
                ->orderBy('pupil_id')
                ->get()
            : null;

        return Inertia::render('Results/ReportCards/Index', [
            'streams' => Stream::where('school_id', $school->id)->with('grade:id,name')->get(['id', 'name', 'grade_id']),
            'terms' => Term::where('school_id', $school->id)->orderBy('number')->get(['id', 'name', 'number']),
            'cards' => $cards,
            'filters' => $request->only('stream_id', 'term_id'),
        ]);
    }

    public function show(ReportCard $reportCard): Response
    {
        abort_if($reportCard->school_id !== app('current_school')?->id, 403);

        $report = $this->resultsService->getPupilTermReport($reportCard->pupil_id, $reportCard->term_id);

        return Inertia::render('Results/ReportCards/Show', [
            'report_card' => $reportCard->load(['pupil', 'term', 'stream.grade']),
            'results' => $report['results'],
            'position' => $report['position'],
            'attendance' => $report['attendance'],
            'answer_sheets' => $report['answer_sheets'],
        ]);
    }

    public function store(GenerateReportCardData $data): RedirectResponse
    {
        $this->authorize('report-card.generate');

        $this->resultsService->generateReportCards($data, auth()->id());

        return back()->with('success', 'Report cards generated.');
    }

    public function update(AddReportCommentData $data, ReportCard $reportCard): RedirectResponse
    {
        abort_if($reportCard->school_id !== app('current_school')?->id, 403);

        $reportCard->update([
            'class_teacher_comment' => $data->class_teacher_comment,
            'headteacher_comment' => $data->headteacher_comment,
            'attendance_days' => $data->attendance_days,
            'attendance_present' => $data->attendance_present,
        ]);

        return back()->with('success', 'Comments saved.');
    }
}
