<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class RecordAttendanceData extends Data
{
    public function __construct(
        #[Required, IntegerType]
        public readonly int $pupil_id,

        #[Required, In(['present', 'absent', 'late', 'excused', 'sick'])]
        public readonly string $status,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $remarks = null,
    ) {}
}
