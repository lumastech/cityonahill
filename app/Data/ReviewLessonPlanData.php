<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ReviewLessonPlanData extends Data
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $comment = null,
    ) {}

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            // A comment is required when rejecting so the teacher gets actionable feedback.
            'comment' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'comment.required_if' => 'Please add a comment explaining why the lesson plan is rejected.',
        ];
    }
}
