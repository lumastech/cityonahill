<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class SendSmsData extends Data
{
    /**
     * @param  string[]  $phones
     */
    public function __construct(
        #[Required, ArrayType]
        public readonly array $phones,

        #[Required, StringType]
        public readonly string $message,

        #[Nullable, In(['airtel', 'mtn'])]
        public readonly ?string $provider = null,
    ) {}
}
