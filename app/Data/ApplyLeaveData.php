<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ApplyLeaveData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('leave_types', 'id')]
        public readonly int $leave_type_id,

        #[Required, Date]
        public readonly string $start_date,

        #[Required, Date]
        public readonly string $end_date,

        #[Required, StringType]
        public readonly string $reason,
    ) {}
}
