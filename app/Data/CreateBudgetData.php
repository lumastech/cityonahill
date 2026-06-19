<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateBudgetData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('academic_years', 'id')]
        public readonly int $academic_year_id,

        #[Nullable, IntegerType, Exists('terms', 'id')]
        public readonly ?int $term_id,

        #[Required, StringType, Max(100)]
        public readonly string $category,

        #[Required, Min(0)]
        public readonly float $amount,
    ) {}
}
