<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class IssueBorrowingData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('library_books', 'id')]
        public readonly int $book_id,

        #[Required, In(['pupil', 'staff'])]
        public readonly string $borrower_type,

        #[Required, IntegerType]
        public readonly int $borrower_id,

        #[Required]
        public readonly string $due_date,
    ) {}
}
