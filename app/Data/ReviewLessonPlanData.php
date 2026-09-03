<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ReviewLessonPlanData extends Data
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $comment = null,
        public readonly ?string $reject_reason = null,
    ) {}

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            // An optional note the reviewer leaves when approving.
            'comment' => ['nullable', 'string', 'max:2000'],
            // A reason is required when rejecting so the teacher gets actionable feedback.
            'reject_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'reject_reason.required_if' => 'Please give a reason why the lesson plan is rejected.',
        ];
    }
}
