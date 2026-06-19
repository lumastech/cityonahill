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

class CreateTermData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('academic_years', 'id')]
        public readonly int $academic_year_id,

        #[Required, StringType]
        public readonly string $name,

        #[Required, IntegerType, In([1, 2, 3])]
        public readonly int $number,

        #[Required, Date]
        public readonly string $start_date,

        #[Required, Date]
        public readonly string $end_date,

        #[Nullable, Date]
        public readonly ?string $ca_deadline = null,

        #[Nullable, Date]
        public readonly ?string $exam_start = null,

        #[Nullable, Date]
        public readonly ?string $exam_end = null,
    ) {}
}
