<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class TransferPupilData extends Data
{
    public function __construct(
        #[Required, In(['external', 'internal'])]
        public readonly string $type,

        #[Required, StringType, Max(150)]
        public readonly string $to_school,

        #[Required, Date]
        public readonly string $transfer_date,

        #[Nullable, StringType]
        public readonly ?string $reason = null,

        #[Nullable, IntegerType, Exists('streams', 'id')]
        public readonly ?int $stream_id = null,
    ) {}
}
