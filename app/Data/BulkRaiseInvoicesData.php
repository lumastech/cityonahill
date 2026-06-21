<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class BulkRaiseInvoicesData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('fee_structures', 'id')]
        public readonly int $fee_structure_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Nullable, IntegerType, Exists('grades', 'id')]
        public readonly ?int $grade_id = null,

        #[Nullable, Date]
        public readonly ?string $due_date = null,
    ) {}
}
