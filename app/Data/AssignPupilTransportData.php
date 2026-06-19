<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class AssignPupilTransportData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('pupils', 'id')]
        public readonly int $pupil_id,

        #[Required, IntegerType, Exists('transport_routes', 'id')]
        public readonly int $route_id,

        #[Required, StringType, Max(100)]
        public readonly string $pickup_point,

        #[Required, In(['to_school', 'from_school', 'both'])]
        public readonly string $direction,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Required, Numeric, Min(0)]
        public readonly float $fee_amount = 0,
    ) {}
}
