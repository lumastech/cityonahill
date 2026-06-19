<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class EnterTermResultData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('pupils', 'id')]
        public readonly int $pupil_id,

        #[Required, IntegerType, Exists('subjects', 'id')]
        public readonly int $subject_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Nullable, Numeric]
        public readonly ?float $ca_marks = null,

        #[Nullable, Numeric]
        public readonly ?float $exam_marks = null,

        #[Nullable, StringType]
        public readonly ?string $teacher_comment = null,
    ) {}
}
