<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EnterScoresData extends Data
{
    /**
     * @param  array<array{pupil_id: int, marks: float, remarks?: string, files?: array<\Illuminate\Http\UploadedFile>}>  $scores
     */
    public function __construct(
        public readonly int $assessment_id,
        public readonly array $scores,
    ) {}

    public static function rules(): array
    {
        return [
            'assessment_id' => ['required', 'integer', 'exists:assessments,id'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.pupil_id' => ['required', 'integer', 'exists:pupils,id'],
            'scores.*.marks' => ['required', 'numeric', 'min:0'],
            'scores.*.remarks' => ['nullable', 'string', 'max:500'],
            'scores.*.files' => ['nullable', 'array', 'max:5'],
            'scores.*.files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
        ];
    }

    public static function messages(): array
    {
        return [
            'scores.*.files.max' => 'You can attach at most 5 answer sheet files per pupil.',
            'scores.*.files.*.mimes' => 'Answer sheets must be an image (JPG, PNG, WEBP), PDF or Word document.',
            'scores.*.files.*.max' => 'Each answer sheet file must be 10MB or smaller.',
        ];
    }
}
