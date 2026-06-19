<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateAssessmentData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('streams', 'id')]
        public readonly int $stream_id,

        #[Required, IntegerType, Exists('subjects', 'id')]
        public readonly int $subject_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Required, StringType, Max(100)]
        public readonly string $name,

        #[Required, In(['ca_test', 'assignment', 'practical', 'mid_term', 'end_of_term'])]
        public readonly string $type,

        #[Required, IntegerType, Min(1), Max(1000)]
        public readonly int $max_marks,

        #[Required, IntegerType, Min(1), Max(100)]
        public readonly int $weight_percent,

        #[Required, Date]
        public readonly string $date,

        #[Nullable, StringType]
        public readonly ?string $instructions = null,
    ) {}
}
