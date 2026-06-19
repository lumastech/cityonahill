<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateExpenseData extends Data
{
    public function __construct(
        #[Required, In(['salaries', 'utilities', 'maintenance', 'supplies', 'transport', 'feeding', 'library', 'other'])]
        public readonly string $category,

        #[Required, StringType]
        public readonly string $description,

        #[Required, Min(0.01)]
        public readonly float $amount,

        #[Required, Date]
        public readonly string $expense_date,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $receipt_no = null,
    ) {}
}
