<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateAcademicYearData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $name,

        #[Required, Date]
        public readonly string $start_date,

        #[Required, Date]
        public readonly string $end_date,

        #[BooleanType]
        public readonly bool $is_current = false,
    ) {}
}
