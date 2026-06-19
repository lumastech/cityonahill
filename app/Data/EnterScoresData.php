<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EnterScoresData extends Data
{
    /**
     * @param  array<array{pupil_id: int, marks: float, remarks?: string}>  $scores
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
        ];
    }
}
