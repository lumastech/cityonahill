<?php

namespace App\Services;

use App\Data\ReviewLessonPlanData;
use App\Data\RevertLessonPlanData;
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
                ...$this->attributesFrom($data),
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
                ...$this->attributesFrom($data),
                // Resubmitting clears the previous review outcome.
                'status' => $data->submit ? 'submitted' : $plan->status,
                'submitted_at' => $data->submit ? now() : $plan->submitted_at,
                'reviewed_by' => $data->submit ? null : $plan->reviewed_by,
                'reviewed_at' => $data->submit ? null : $plan->reviewed_at,
                'reverted_by' => $data->submit ? null : $plan->reverted_by,
                'reverted_at' => $data->submit ? null : $plan->reverted_at,
                'comment' => $data->submit ? null : $plan->comment,
                'reject_reason' => $data->submit ? null : $plan->reject_reason,
                'revert_reason' => $data->submit ? null : $plan->revert_reason,
            ]);

            $this->attachFiles($plan, $data);

            return $plan;
        });
    }

    public function review(LessonPlan $plan, ReviewLessonPlanData $data, int $reviewerId): LessonPlan
    {
        $rejected = $data->status === 'rejected';

        $plan->update([
            'status' => $data->status,
            'comment' => $rejected ? null : $data->comment,
            'reject_reason' => $rejected ? $data->reject_reason : null,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            // A fresh decision supersedes any earlier one that was withdrawn.
            'revert_reason' => null,
            'reverted_by' => null,
            'reverted_at' => null,
        ]);

        return $plan;
    }

    /**
     * Withdraws a decision that has already been made and hands the plan back to its
     * author. The decision it replaces is kept on the record so both are readable.
     */
    public function revert(LessonPlan $plan, RevertLessonPlanData $data, int $reverterId): LessonPlan
    {
        $plan->update([
            'status' => 'reverted',
            'revert_reason' => $data->revert_reason,
            'reverted_by' => $reverterId,
            'reverted_at' => now(),
        ]);

        return $plan;
    }

    /**
     * The lesson plan template fields, shared by create and update.
     *
     * @return array<string, mixed>
     */
    private function attributesFrom(StoreLessonPlanData $data): array
    {
        return [
            'topic' => $data->topic,
            'sub_topic' => $data->sub_topic,
            'general_competence' => $data->general_competence,
            'specific_competence' => $data->specific_competence,
            'lesson_goal' => $data->lesson_goal,
            'reference' => $data->reference,
            'prior_knowledge' => $data->prior_knowledge,
            'learning_material' => $data->learning_material,
            'learning_environment' => $data->learning_environment,
            'stages' => $data->stages,
            'conclusion' => $data->conclusion,
            'evaluation' => $data->evaluation,
            'week_number' => $data->week_number,
            'lesson_date' => $data->lesson_date,
            'duration_minutes' => $data->duration_minutes,
            'boys_count' => $data->boys_count,
            'girls_count' => $data->girls_count,
        ];
    }

    private function attachFiles(LessonPlan $plan, StoreLessonPlanData $data): void
    {
        foreach (array_filter($data->attachments) as $file) {
            $plan->addMedia($file)->toMediaCollection(LessonPlan::ATTACHMENTS);
        }
    }
}
