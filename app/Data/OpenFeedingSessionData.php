<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OpenFeedingSessionData extends Data
{
    public function __construct(
        #[Required, Date]
        public readonly string $date,

        #[Required, In(['breakfast', 'lunch', 'snack'])]
        public readonly string $meal_type,

        #[Nullable, IntegerType, Exists('streams', 'id')]
        public readonly ?int $stream_id = null,
    ) {}
}
