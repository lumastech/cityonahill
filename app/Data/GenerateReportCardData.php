<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class GenerateReportCardData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('streams', 'id')]
        public readonly int $stream_id,

        #[Required, IntegerType, Exists('terms', 'id')]
        public readonly int $term_id,
    ) {}
}
