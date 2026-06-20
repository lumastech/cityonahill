<?php

namespace App\Services;

use App\Data\BulkEnterTermResultsData;
use App\Data\CreateAssessmentData;
use App\Data\EnterScoresData;
use App\Data\GenerateReportCardData;
use App\Data\PublishResultsData;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Pupil;
use App\Models\ReportCard;
use App\Models\Stream;
use App\Models\TermResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultsService
{
    public function createAssessment(int $schoolId, CreateAssessmentData $data): Assessment
    {
        return Assessment::create([
            'school_id' => $schoolId,
            'stream_id' => $data->stream_id,
            'subject_id' => $data->subject_id,
            'term_id' => $data->term_id,
            'name' => $data->name,
            'type' => $data->type,
            'max_marks' => $data->max_marks,
            'weight_percent' => $data->weight_percent,
            'date' => $data->date,
            'instructions' => $data->instructions,
            'created_by' => auth()->id(),
        ]);
    }

    public function enterScores(EnterScoresData $data, int $enteredBy): Collection
    {
        $assessment = Assessment::findOrFail($data->assessment_id);
        $now = now();

        $rows = collect($data->scores)->map(function ($score) use ($assessment, $enteredBy, $now) {
            $pct = $assessment->max_marks > 0
                ? ($score['marks'] / $assessment->max_marks) * 100
                : 0;

            return [
                'assessment_id' => $assessment->id,
                'pupil_id' => $score['pupil_id'],
                'marks_obtained' => $score['marks'],
                'grade_letter' => TermResult::computeGradeLetter($pct),
                'remarks' => $score['remarks'] ?? null,
                'entered_by' => $enteredBy,
                'entered_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        AssessmentScore::upsert(
            $rows,
            ['assessment_id', 'pupil_id'],
            ['marks_obtained', 'grade_letter', 'remarks', 'entered_by', 'entered_at', 'updated_at']
        );

        return AssessmentScore::where('assessment_id', $assessment->id)
            ->with('pupil:id,first_name,last_name,admission_no')
            ->get();
    }

    public function computeCAMarks(int $pupilId, int $subjectId, int $termId): ?float
    {
        $caTypes = ['ca_test', 'assignment', 'practical', 'mid_term'];

        $assessments = Assessment::where('subject_id', $subjectId)
            ->where('term_id', $termId)
            ->whereIn('type', $caTypes)
            ->with(['scores' => fn ($q) => $q->where('pupil_id', $pupilId)])
            ->get();

        if ($assessments->isEmpty()) {
            return null;
        }

        $totalWeight = $assessments->sum('weight_percent');

        if ($totalWeight === 0) {
            return null;
        }

        $weightedSum = $assessments->sum(function ($assessment) {
            $score = $assessment->scores->first();
            if (! $score || $assessment->max_marks === 0) {
                return 0;
            }

            $pct = ($score->marks_obtained / $assessment->max_marks) * 100;

            return $pct * $assessment->weight_percent;
        });

        return round($weightedSum / $totalWeight, 2);
    }

    public function computeExamMarks(int $pupilId, int $subjectId, int $termId): ?float
    {
        $assessment = Assessment::where('subject_id', $subjectId)
            ->where('term_id', $termId)
            ->where('type', 'end_of_term')
            ->with(['scores' => fn ($q) => $q->where('pupil_id', $pupilId)])
            ->latest('date')
            ->first();

        if (! $assessment || $assessment->max_marks === 0) {
            return null;
        }

        $score = $assessment->scores->first();

        if (! $score) {
            return null;
        }

        return round(($score->marks_obtained / $assessment->max_marks) * 100, 2);
    }

    public function enterTermResults(BulkEnterTermResultsData $data, int $enteredBy): Collection
    {
        $now = now();

        $stream = Stream::with('grade:id,academic_year_id')->findOrFail($data->stream_id);

        $rows = collect($data->results)->map(function ($result) use ($data, $stream, $now) {
            $ca = $result['ca_marks'] ?? null;
            $exam = $result['exam_marks'] ?? null;

            $total = null;
            if ($ca !== null && $exam !== null) {
                $total = round(($ca + $exam) / 2, 2);
            } elseif ($ca !== null) {
                $total = $ca;
            } elseif ($exam !== null) {
                $total = $exam;
            }

            $grade = $total !== null ? TermResult::computeGradeLetter((float) $total) : null;
            $points = $grade !== null ? TermResult::computePoints($grade) : null;

            $pupil = Pupil::find($result['pupil_id']);

            return [
                'school_id' => $stream->school_id,
                'pupil_id' => $result['pupil_id'],
                'subject_id' => $data->subject_id,
                'term_id' => $data->term_id,
                'academic_year_id' => $pupil?->academic_year_id,
                'stream_id' => $data->stream_id,
                'ca_marks' => $ca,
                'exam_marks' => $exam,
                'total_marks' => $total,
                'grade_letter' => $grade,
                'points' => $points,
                'teacher_comment' => $result['teacher_comment'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        TermResult::upsert(
            $rows,
            ['school_id', 'pupil_id', 'subject_id', 'term_id'],
            ['ca_marks', 'exam_marks', 'total_marks', 'grade_letter', 'points', 'teacher_comment', 'updated_at']
        );

        return TermResult::where('stream_id', $data->stream_id)
            ->where('subject_id', $data->subject_id)
            ->where('term_id', $data->term_id)
            ->with('pupil:id,first_name,last_name,admission_no')
            ->get();
    }

    public function computeStreamPositions(int $streamId, int $termId): void
    {
        $results = TermResult::where('stream_id', $streamId)
            ->where('term_id', $termId)
            ->whereNotNull('total_marks')
            ->orderByDesc('total_marks')
            ->get();

        $grouped = $results->groupBy('subject_id');

        DB::transaction(function () use ($grouped) {
            foreach ($grouped as $subjectResults) {
                $rank = 1;
                foreach ($subjectResults->sortByDesc('total_marks')->values() as $result) {
                    $result->update(['position_in_stream' => $rank++]);
                }
            }
        });
    }

    public function publishResults(PublishResultsData $data, int $publishedBy): int
    {
        return TermResult::where('stream_id', $data->stream_id)
            ->where('term_id', $data->term_id)
            ->update(['published' => 1]);
    }

    public function generateReportCards(GenerateReportCardData $data, int $generatedBy): Collection
    {
        $pupils = Pupil::where('stream_id', $data->stream_id)
            ->where('status', 'active')
            ->get(['id', 'school_id', 'academic_year_id']);

        $now = now();

        return DB::transaction(function () use ($data, $pupils, $generatedBy, $now) {
            return $pupils->map(function ($pupil) use ($data, $generatedBy, $now) {
                $results = TermResult::where('pupil_id', $pupil->id)
                    ->where('term_id', $data->term_id)
                    ->where('published', 1)
                    ->get();

                $average = $results->isNotEmpty()
                    ? round($results->avg('total_marks'), 2)
                    : null;

                return ReportCard::updateOrCreate(
                    [
                        'school_id' => $pupil->school_id,
                        'pupil_id' => $pupil->id,
                        'term_id' => $data->term_id,
                    ],
                    [
                        'academic_year_id' => $pupil->academic_year_id,
                        'stream_id' => $data->stream_id,
                        'generated_at' => $now,
                        'generated_by' => $generatedBy,
                    ]
                );
            });
        });
    }

    public function publishReportCards(int $streamId, int $termId): int
    {
        return ReportCard::where('stream_id', $streamId)
            ->where('term_id', $termId)
            ->update(['published_at' => now()]);
    }

    public function getPupilTermReport(int $pupilId, int $termId): array
    {
        $reportCard = ReportCard::where('pupil_id', $pupilId)
            ->where('term_id', $termId)
            ->with(['pupil:id,first_name,last_name,admission_no', 'term:id,name,number'])
            ->first();

        $results = TermResult::where('pupil_id', $pupilId)
            ->where('term_id', $termId)
            ->with('subject:id,name,code')
            ->get();

        $streamResults = $reportCard
            ? TermResult::where('stream_id', $reportCard->stream_id)
                ->where('term_id', $termId)
                ->where('published', 1)
                ->selectRaw('pupil_id, SUM(total_marks) as grand_total')
                ->groupBy('pupil_id')
                ->orderByDesc('grand_total')
                ->get()
            : collect();

        $position = $streamResults->search(fn ($r) => $r->pupil_id === $pupilId);
        $position = $position !== false ? $position + 1 : null;

        return [
            'report_card' => $reportCard,
            'results' => $results,
            'position' => $position,
            'attendance' => [
                'days' => $reportCard?->attendance_days,
                'present' => $reportCard?->attendance_present,
            ],
        ];
    }
}
