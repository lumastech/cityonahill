<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class StoreLessonPlanData extends Data
{
    /**
     * @param  array<int, array{stage: string, teacher_activity: ?string, learner_activity: ?string, assessment_criteria: ?string}>  $stages
     * @param  array<int, UploadedFile>  $attachments
     */
    public function __construct(
        public readonly int $subject_id,
        public readonly int $stream_id,
        public readonly int $term_id,
        public readonly string $topic,
        public readonly string $general_competence,
        public readonly string $specific_competence,
        public readonly string $lesson_goal,
        public readonly ?string $sub_topic = null,
        public readonly ?string $reference = null,
        public readonly ?string $prior_knowledge = null,
        public readonly ?string $learning_material = null,
        public readonly ?string $learning_environment = null,
        public readonly array $stages = [],
        public readonly ?string $conclusion = null,
        public readonly ?string $evaluation = null,
        public readonly ?int $week_number = null,
        public readonly ?string $lesson_date = null,
        public readonly ?int $duration_minutes = null,
        public readonly ?int $boys_count = null,
        public readonly ?int $girls_count = null,
        public readonly bool $submit = false,
        public readonly array $attachments = [],
    ) {}

    /**
     * Blank stage rows — a row the teacher added but never filled in — are dropped
     * before validation, and the remaining cells are trimmed to null when empty.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        if (! is_array($properties['stages'] ?? null)) {
            return $properties;
        }

        $properties['stages'] = collect($properties['stages'])
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => [
                'stage' => trim((string) ($row['stage'] ?? '')),
                'teacher_activity' => self::cell($row['teacher_activity'] ?? null),
                'learner_activity' => self::cell($row['learner_activity'] ?? null),
                'assessment_criteria' => self::cell($row['assessment_criteria'] ?? null),
            ])
            ->reject(fn (array $row) => $row['stage'] === ''
                && $row['teacher_activity'] === null
                && $row['learner_activity'] === null
                && $row['assessment_criteria'] === null)
            ->values()
            ->all();

        return $properties;
    }

    private static function cell(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'stream_id' => ['required', 'integer', 'exists:streams,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'topic' => ['required', 'string', 'max:255'],
            'sub_topic' => ['nullable', 'string', 'max:255'],
            'general_competence' => ['required', 'string'],
            'specific_competence' => ['required', 'string'],
            'lesson_goal' => ['required', 'string'],
            'reference' => ['nullable', 'string'],
            'prior_knowledge' => ['nullable', 'string'],
            'learning_material' => ['nullable', 'string'],
            'learning_environment' => ['nullable', 'string'],
            'stages' => ['required', 'array', 'min:1', 'max:10'],
            'stages.*.stage' => ['required', 'string', 'max:100'],
            'stages.*.teacher_activity' => ['nullable', 'string'],
            'stages.*.learner_activity' => ['nullable', 'string'],
            'stages.*.assessment_criteria' => ['nullable', 'string'],
            'conclusion' => ['nullable', 'string'],
            'evaluation' => ['nullable', 'string'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:52'],
            'lesson_date' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'boys_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'girls_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'submit' => ['boolean'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,ppt,pptx,xls,xlsx', 'max:20480'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'stages.required' => 'A lesson plan needs at least one stage.',
            'stages.*.stage.required' => 'Each stage needs a name.',
            'attachments.max' => 'You can attach at most 10 files per lesson plan.',
            'attachments.*.mimes' => 'Attachments must be an image, PDF, Word, PowerPoint or Excel file.',
            'attachments.*.max' => 'Each attachment must be 20MB or smaller.',
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'topic' => 'topic',
            'general_competence' => 'general competence',
            'specific_competence' => 'specific competence',
            'lesson_goal' => 'lesson goal',
            'learning_material' => 'learning material',
            'learning_environment' => 'learning environment',
            'duration_minutes' => 'duration',
        ];
    }
}
