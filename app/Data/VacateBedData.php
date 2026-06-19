<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class VacateBedData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public readonly ?string $reason = null,
    ) {}
}
