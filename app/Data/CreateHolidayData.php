<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateHolidayData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $name,

        #[Required, Date]
        public readonly string $start_date,

        #[Required, Date]
        public readonly string $end_date,

        #[Required, In(['public_holiday', 'school_holiday', 'event'])]
        public readonly string $type,

        #[Nullable, IntegerType, Exists('terms', 'id')]
        public readonly ?int $term_id = null,
    ) {}
}
