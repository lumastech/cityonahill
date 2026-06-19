<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ApproveLeaveData extends Data
{
    public function __construct(
        #[Required, In(['approved', 'rejected'])]
        public readonly string $status,

        #[Nullable, StringType]
        public readonly ?string $comment = null,
    ) {}
}
