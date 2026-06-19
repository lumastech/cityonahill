<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class AddReportCommentData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('pupils', 'id')]
        public readonly int $pupil_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Nullable, StringType]
        public readonly ?string $class_teacher_comment = null,

        #[Nullable, StringType]
        public readonly ?string $headteacher_comment = null,

        #[Required, IntegerType, Min(0)]
        public readonly int $attendance_days = 0,

        #[Required, IntegerType, Min(0)]
        public readonly int $attendance_present = 0,
    ) {}
}
