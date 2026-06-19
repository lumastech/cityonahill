<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class SendMessageData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('users', 'id')]
        public readonly int $recipient_id,

        #[Required, StringType]
        public readonly string $message,

        #[Nullable, IntegerType, Exists('pupils', 'id')]
        public readonly ?int $pupil_id = null,
    ) {}
}
