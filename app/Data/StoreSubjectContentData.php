<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class StoreSubjectContentData extends Data
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $body = null,
        public readonly ?int $grade_id = null,
        public readonly int $sort_order = 0,
        public readonly array $files = [],
    ) {}

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'files' => ['nullable', 'array', 'max:15'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,ppt,pptx,xls,xlsx,mp4,mp3', 'max:51200'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'files.max' => 'You can attach at most 15 files per learning content item.',
            'files.*.mimes' => 'Files must be an image, document, presentation, spreadsheet, audio or video file.',
            'files.*.max' => 'Each file must be 50MB or smaller.',
        ];
    }
}
