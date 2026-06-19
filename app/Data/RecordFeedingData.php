<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class RecordFeedingData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('feeding_sessions', 'id')]
        public readonly int $session_id,

        #[Required, ArrayType]
        public readonly array $pupil_ids,
    ) {}
}
