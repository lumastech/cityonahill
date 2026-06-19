<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OpenAttendanceSessionData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('streams', 'id')]
        public readonly int $stream_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,

        #[Required, Date]
        public readonly string $date,

        #[In(['morning', 'afternoon', 'full_day'])]
        public readonly string $session_type = 'full_day',
    ) {}
}
