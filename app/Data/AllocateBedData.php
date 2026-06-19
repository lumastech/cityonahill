<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AllocateBedData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('pupils', 'id')]
        public readonly int $pupil_id,

        #[Required, IntegerType, Exists('beds', 'id')]
        public readonly int $bed_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Required, Numeric, Min(0)]
        public readonly float $fee_amount = 0,
    ) {}
}
