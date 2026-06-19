<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class AddBookData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $title,

        #[Required, StringType, Max(255)]
        public readonly string $author,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $isbn = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $publisher = null,

        #[Nullable, IntegerType, Min(1000)]
        public readonly ?int $publish_year = null,

        #[Required, StringType, Max(100)]
        public readonly string $category = 'General',

        #[Nullable, IntegerType, Exists('subjects', 'id')]
        public readonly ?int $subject_id = null,

        #[Required, IntegerType, Min(1)]
        public readonly int $copies_total = 1,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $shelf_location = null,

        #[Nullable, StringType]
        public readonly ?string $description = null,
    ) {}
}
