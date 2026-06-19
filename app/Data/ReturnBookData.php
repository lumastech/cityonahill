<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ReturnBookData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('book_borrowings', 'id')]
        public readonly int $borrowing_id,

        #[Required, Date]
        public readonly string $returned_date,

        #[Nullable, Min(0)]
        public readonly ?float $fine_paid = null,
    ) {}
}
