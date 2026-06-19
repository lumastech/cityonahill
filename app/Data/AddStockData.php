<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class AddStockData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $item_name,

        #[Required, StringType, Max(20)]
        public readonly string $unit,

        #[Required, Numeric, Min(0)]
        public readonly float $quantity,

        #[Required, Numeric, Min(0)]
        public readonly float $reorder_level = 0,

        #[Nullable, Numeric, Min(0)]
        public readonly ?float $cost_per_unit = null,
    ) {}
}
