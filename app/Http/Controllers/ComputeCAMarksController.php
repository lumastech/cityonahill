<?php

namespace App\Http\Controllers;

use App\Models\GradeSubject;
use App\Models\Pupil;
use App\Models\Stream;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComputeCAMarksController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stream_id'  => ['required', 'integer', 'exists:streams,id'],
            'term_id'    => ['required', 'integer', 'exists:terms,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);

        $school = app('current_school');

        $stream = Stream::findOrFail($validated['stream_id']);
        $term   = Term::findOrFail($validated['term_id']);

        // When no subject given, compute for every subject assigned to this grade
        $subjectIds = $validated['subject_id']
            ? [$validated['subject_id']]
            : GradeSubject::where('grade_id', $stream->grade_id)->pluck('subject_id')->all();

        $pupils = Pupil::where('stream_id', $validated['stream_id'])
            ->where('status', 'active')
            ->pluck('id');

        foreach ($subjectIds as $subjectId) {
            foreach ($pupils as $pupilId) {
                $ca   = $this->resultsService->computeCAMarks($pupilId, $subjectId, $validated['term_id']);
                $exam = $this->resultsService->computeExamMarks($pupilId, $subjectId, $validated['term_id']);

                // Derive total, grade, points
                $total = match (true) {
                    $ca !== null && $exam !== null => round(($ca + $exam) / 2, 2),
                    $ca   !== null                 => $ca,
                    $exam !== null                 => $exam,
                    default                        => null,
                };

                $grade  = $total !== null ? TermResult::computeGradeLetter((float) $total) : null;
                $points = $grade  !== null ? TermResult::computePoints($grade)              : null;

                $values = [
                    'academic_year_id' => $term->academic_year_id,
                    'stream_id'        => $validated['stream_id'],
                    'ca_marks'         => $ca,
                    'total_marks'      => $total,
                    'grade_letter'     => $grade,
                    'points'           => $points,
                ];

                if ($exam !== null) {
                    $values['exam_marks'] = $exam;
                }

                TermResult::updateOrCreate(
                    [
                        'school_id'  => $school->id,
                        'pupil_id'   => $pupilId,
                        'subject_id' => $subjectId,
                        'term_id'    => $validated['term_id'],
                    ],
                    $values,
                );
            }
        }

        $count = count($subjectIds);
        $label = $count === 1 ? '1 subject' : "{$count} subjects";

        return back()->with('success', "CA marks computed from assessments for {$label}.");
    }
}
