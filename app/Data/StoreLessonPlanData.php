<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class StoreLessonPlanData extends Data
{
    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function __construct(
        public readonly int $subject_id,
        public readonly int $stream_id,
        public readonly int $term_id,
        public readonly string $title,
        public readonly string $objectives,
        public readonly string $content,
        public readonly ?int $week_number = null,
        public readonly ?string $lesson_date = null,
        public readonly ?string $activities = null,
        public readonly ?string $materials = null,
        public readonly bool $submit = false,
        public readonly array $attachments = [],
    ) {}

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'stream_id' => ['required', 'integer', 'exists:streams,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'title' => ['required', 'string', 'max:255'],
            'objectives' => ['required', 'string'],
            'content' => ['required', 'string'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:52'],
            'lesson_date' => ['nullable', 'date'],
            'activities' => ['nullable', 'string'],
            'materials' => ['nullable', 'string'],
            'submit' => ['boolean'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,ppt,pptx,xls,xlsx', 'max:20480'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'attachments.max' => 'You can attach at most 10 files per lesson plan.',
            'attachments.*.mimes' => 'Attachments must be an image, PDF, Word, PowerPoint or Excel file.',
            'attachments.*.max' => 'Each attachment must be 20MB or smaller.',
        ];
    }
}
