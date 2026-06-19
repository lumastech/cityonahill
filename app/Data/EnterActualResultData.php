<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EnterActualResultData extends Data
{
    /**
     * @param  array<array{candidate_id: int, subject_id: int, actual_grade: string, actual_points: int}>  $results
     */
    public function __construct(
        public readonly array $results,
    ) {}

    public static function rules(): array
    {
        return [
            'results' => ['required', 'array', 'min:1'],
            'results.*.candidate_id' => ['required', 'integer', 'exists:ecz_candidates,id'],
            'results.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'results.*.actual_grade' => ['required', 'string', 'in:A,B,C,D,E,F,U,X'],
            'results.*.actual_points' => ['required', 'integer', 'min:1', 'max:9'],
        ];
    }
}
