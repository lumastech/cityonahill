<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreTimetableSlotData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('streams', 'id')]
        public readonly int $stream_id,

        #[Required, IntegerType, Exists('subjects', 'id')]
        public readonly int $subject_id,

        #[Required, IntegerType, Exists('users', 'id')]
        public readonly int $teacher_id,

        #[Required, IntegerType, In([1, 2, 3, 4, 5])]
        public readonly int $day_of_week,

        #[Required, IntegerType]
        public readonly int $period_number,

        #[Required, DateFormat('H:i')]
        public readonly string $start_time,

        #[Required, DateFormat('H:i')]
        public readonly string $end_time,

        #[Nullable, StringType]
        public readonly ?string $room = null,
    ) {}
}
