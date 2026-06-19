<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateRouteData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $name,

        #[Required, ArrayType]
        public readonly array $pickup_points,

        #[Required, IntegerType, Min(1)]
        public readonly int $capacity = 50,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $vehicle_registration = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $vehicle_type = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $driver_name = null,

        #[Nullable, StringType, Max(25)]
        public readonly ?string $driver_phone = null,
    ) {}
}
