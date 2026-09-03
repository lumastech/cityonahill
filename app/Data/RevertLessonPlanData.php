<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class RevertLessonPlanData extends Data
{
    public function __construct(
        public readonly string $revert_reason,
    ) {}

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'revert_reason' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'revert_reason.required' => 'Please give a reason for withdrawing this decision.',
        ];
    }
}
