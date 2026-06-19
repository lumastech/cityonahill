<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class RecordStockMovementData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('feeding_stock', 'id')]
        public readonly int $stock_id,

        #[Required, In(['restock', 'consumption', 'wastage', 'adjustment'])]
        public readonly string $type,

        #[Required, Numeric, Min(0.01)]
        public readonly float $quantity,

        #[Nullable, StringType]
        public readonly ?string $notes = null,
    ) {}
}
