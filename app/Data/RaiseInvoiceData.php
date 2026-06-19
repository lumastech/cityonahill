<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class RaiseInvoiceData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('pupils', 'id')]
        public readonly int $pupil_id,

        #[Required, IntegerType, Exists('fee_structures', 'id')]
        public readonly int $fee_structure_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Min(0)]
        public readonly float $discount = 0.0,

        #[Nullable, Date]
        public readonly ?string $due_date = null,
    ) {}
}
