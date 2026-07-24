<?php

namespace App\Services;

use App\Data\ReviewLessonPlanData;
use App\Data\StoreLessonPlanData;
use App\Models\LessonPlan;
use Illuminate\Support\Facades\DB;

class LessonPlanService
{
    public function create(int $schoolId, int $userId, StoreLessonPlanData $data): LessonPlan
    {
        return DB::transaction(function () use ($schoolId, $userId, $data) {
            $plan = LessonPlan::create([
                'school_id' => $schoolId,
                'subject_id' => $data->subject_id,
                'stream_id' => $data->stream_id,
                'term_id' => $data->term_id,
                'title' => $data->title,
                'week_number' => $data->week_number,
                'lesson_date' => $data->lesson_date,
                'objectives' => $data->objectives,
                'content' => $data->content,
                'activities' => $data->activities,
                'materials' => $data->materials,
                'status' => $data->submit ? 'submitted' : 'draft',
                'submitted_by' => $userId,
                'submitted_at' => $data->submit ? now() : null,
            ]);

            $this->attachFiles($plan, $data);

            return $plan;
        });
    }

    public function update(LessonPlan $plan, StoreLessonPlanData $data): LessonPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update([
                'subject_id' => $data->subject_id,
                'stream_id' => $data->stream_id,
                'term_id' => $data->term_id,
                'title' => $data->title,
                'week_number' => $data->week_number,
                'lesson_date' => $data->lesson_date,
                'objectives' => $data->objectives,
                'content' => $data->content,
                'activities' => $data->activities,
                'materials' => $data->materials,
                // Resubmitting clears the previous review outcome.
                'status' => $data->submit ? 'submitted' : $plan->status,
                'submitted_at' => $data->submit ? now() : $plan->submitted_at,
                'reviewed_by' => $data->submit ? null : $plan->reviewed_by,
                'reviewed_at' => $data->submit ? null : $plan->reviewed_at,
                'comment' => $data->submit ? null : $plan->comment,
            ]);

            $this->attachFiles($plan, $data);

            return $plan;
        });
    }

    public function review(LessonPlan $plan, ReviewLessonPlanData $data, int $reviewerId): LessonPlan
    {
        $plan->update([
            'status' => $data->status,
            'comment' => $data->comment,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $plan;
    }

    private function attachFiles(LessonPlan $plan, StoreLessonPlanData $data): void
    {
        foreach (array_filter($data->attachments) as $file) {
            $plan->addMedia($file)->toMediaCollection(LessonPlan::ATTACHMENTS);
        }
    }
}
