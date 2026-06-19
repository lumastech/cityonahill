<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateIndexNumberData extends Data
{
    public function __construct(
        #[Required, StringType, Max(30)]
        public readonly string $index_number,

        #[Required, StringType, Max(20)]
        public readonly string $centre_number,
    ) {}
}
