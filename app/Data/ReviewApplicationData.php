<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ReviewApplicationData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(2000)]
        public readonly ?string $reviewer_notes = null,
    ) {}
}
