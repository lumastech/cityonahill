<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateFeeStructureData extends Data
{
    public function __construct(
        #[Nullable, IntegerType, Exists('grades', 'id')]
        public readonly ?int $grade_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Required, IntegerType, Exists('academic_years', 'id')]
        public readonly int $academic_year_id,

        #[Required, StringType, Max(100)]
        public readonly string $name,

        #[Nullable, StringType]
        public readonly ?string $description,

        #[Required, Min(0)]
        public readonly float $amount,

        #[Required, In(['all', 'day_scholars', 'boarders', 'new_pupils'])]
        public readonly string $applies_to = 'all',

        #[BooleanType]
        public readonly bool $is_mandatory = true,
    ) {}
}
