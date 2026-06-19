<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BulkEnterTermResultsData extends Data
{
    /**
     * @param  EnterTermResultData[]  $results
     */
    public function __construct(
        public readonly int $stream_id,
        public readonly int $subject_id,
        public readonly int $term_id,
        public readonly array $results,
    ) {}

    public static function rules(): array
    {
        return [
            'stream_id' => ['required', 'integer', 'exists:streams,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.pupil_id' => ['required', 'integer', 'exists:pupils,id'],
            'results.*.ca_marks' => ['nullable', 'numeric', 'min:0'],
            'results.*.exam_marks' => ['nullable', 'numeric', 'min:0'],
            'results.*.teacher_comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
