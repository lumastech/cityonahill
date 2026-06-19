<?php

namespace App\Services;

use App\Data\RegisterCandidateData;
use App\Data\UpdateIndexNumberData;
use App\Models\EczCandidate;
use App\Models\EczSubjectEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EczService
{
    public function registerCandidate(int $schoolId, RegisterCandidateData $data): EczCandidate
    {
        return EczCandidate::firstOrCreate(
            [
                'school_id' => $schoolId,
                'pupil_id' => $data->pupil_id,
                'exam_year' => $data->exam_year,
                'grade_level' => $data->grade_level,
            ],
            ['registration_status' => 'pending'],
        );
    }

    public function addSubjectEntries(int $candidateId, array $subjectIds, int $enteredBy): Collection
    {
        $entries = collect();

        foreach ($subjectIds as $subjectId) {
            $entries->push(EczSubjectEntry::firstOrCreate(
                [
                    'candidate_id' => $candidateId,
                    'subject_id' => $subjectId,
                ],
                ['entered_by' => $enteredBy],
            ));
        }

        return $entries;
    }

    public function setPredictedGrade(int $entryId, string $grade): EczSubjectEntry
    {
        $entry = EczSubjectEntry::findOrFail($entryId);
        $entry->update(['predicted_grade' => strtoupper($grade)]);

        return $entry;
    }

    public function updateIndexNumber(int $candidateId, UpdateIndexNumberData $data): EczCandidate
    {
        $candidate = EczCandidate::findOrFail($candidateId);

        $candidate->update([
            'index_number' => $data->index_number,
            'centre_number' => $data->centre_number,
            'registration_status' => 'submitted',
        ]);

        return $candidate;
    }

    /** @return Collection<int, EczSubjectEntry> */
    public function enterActualResults(array $results): Collection
    {
        $updated = collect();

        DB::transaction(function () use ($results, &$updated) {
            foreach ($results as $row) {
                $entry = EczSubjectEntry::where('candidate_id', $row['candidate_id'])
                    ->where('subject_id', $row['subject_id'])
                    ->firstOrFail();

                $entry->update([
                    'actual_grade' => strtoupper($row['actual_grade']),
                    'actual_points' => $row['actual_points'],
                ]);

                $updated->push($entry);
            }

            // Recompute division for each affected candidate.
            $candidateIds = collect($results)->pluck('candidate_id')->unique();
            foreach ($candidateIds as $candidateId) {
                $candidate = EczCandidate::findOrFail($candidateId);
                $candidate->load('subjectEntries');
                $division = $candidate->computeDivision();
                $totalPoints = $candidate->subjectEntries
                    ->whereNotNull('actual_points')
                    ->sortBy('actual_points')
                    ->take($candidate->grade_level === 7 ? 4 : 6)
                    ->sum('actual_points');

                $candidate->update([
                    'division' => $division,
                    'total_points' => $totalPoints,
                    'registration_status' => 'confirmed',
                ]);
            }
        });

        return $updated;
    }

    public function getCandidateList(int $schoolId, int $gradeLevel, int $examYear): Collection
    {
        return EczCandidate::where('school_id', $schoolId)
            ->forGradeLevel($gradeLevel)
            ->forExamYear($examYear)
            ->with([
                'pupil:id,first_name,last_name,admission_no',
                'subjectEntries.subject:id,name,code',
            ])
            ->orderBy('id')
            ->get();
    }

    public function getSchoolPassRate(int $schoolId, int $gradeLevel, int $examYear): array
    {
        $candidates = EczCandidate::where('school_id', $schoolId)
            ->forGradeLevel($gradeLevel)
            ->forExamYear($examYear)
            ->whereNotNull('division')
            ->get(['division', 'total_points']);

        $registered = $candidates->count();
        $div1 = $candidates->where('division', 'Division I')->count();
        $div2 = $candidates->where('division', 'Division II')->count();
        $div3 = $candidates->where('division', 'Division III')->count();
        $div4 = $candidates->where('division', 'Division IV')->count();
        $failed = $candidates->where('division', 'Fail')->count();
        $passed = $div1 + $div2 + $div3 + $div4;

        return [
            'registered' => $registered,
            'passed' => $passed,
            'failed' => $failed,
            'div1' => $div1,
            'div2' => $div2,
            'div3' => $div3,
            'div4' => $div4,
            'pass_rate_pct' => $registered > 0 ? round(($passed / $registered) * 100, 1) : 0.0,
        ];
    }
}
