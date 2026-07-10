<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreGradeData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $name,

        #[Required, IntegerType, Min(0), Max(12)]
        public readonly int $grade_number,

        #[Required, In(['ece', 'primary', 'junior_secondary', 'senior_secondary'])]
        public readonly string $level,

        #[BooleanType]
        public readonly bool $is_ecz_year = false,

        #[IntegerType, Min(0)]
        public readonly int $order_index = 0,
    ) {}
}
