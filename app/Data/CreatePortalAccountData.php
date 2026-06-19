<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CreatePortalAccountData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('guardians', 'id')]
        public readonly int $guardian_id,

        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
    ) {}
}
