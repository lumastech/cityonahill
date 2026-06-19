<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateDormitoryData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $name,

        #[Required, In(['male', 'female'])]
        public readonly string $gender,

        #[Required, IntegerType, Min(1)]
        public readonly int $capacity,

        #[Nullable, StringType]
        public readonly ?string $description = null,
    ) {}
}
