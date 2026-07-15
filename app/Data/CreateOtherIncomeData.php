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

class CreateOtherIncomeData extends Data
{
    public function __construct(
        #[Required, In(['donation', 'grant', 'uniform_sales', 'book_sales', 'feeding', 'rental', 'fundraising', 'other'])]
        public readonly string $source,

        #[Required, StringType]
        public readonly string $description,

        #[Required, Min(0.01)]
        public readonly float $amount,

        #[Required, Date]
        public readonly string $received_date,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $reference = null,
    ) {}
}
