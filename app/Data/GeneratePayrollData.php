<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class GeneratePayrollData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1), Max(12)]
        public readonly int $month,

        #[Required, IntegerType, Min(2000)]
        public readonly int $year,

        #[BooleanType]
        public readonly bool $include_all_staff = true,
    ) {}
}
