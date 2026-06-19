<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class StoreGuardianData extends Data
{
    public function __construct(
        #[Required, StringType, Max(50)]
        public readonly string $first_name,

        #[Required, StringType, Max(50)]
        public readonly string $last_name,

        #[Required, In(['father', 'mother', 'guardian', 'grandparent', 'sibling', 'other'])]
        public readonly string $relationship,

        #[Required, StringType, Max(25)]
        public readonly string $phone,

        #[Nullable, StringType, Max(25)]
        public readonly ?string $phone2 = null,

        #[Nullable, Email, Unique('guardians', 'email')]
        public readonly ?string $email = null,

        #[Nullable, StringType, Max(25)]
        public readonly ?string $nrc = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $occupation = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $employer = null,

        #[Nullable, StringType]
        public readonly ?string $address = null,

        #[BooleanType]
        public readonly bool $is_primary = false,

        #[BooleanType]
        public readonly bool $is_emergency = false,

        #[BooleanType]
        public readonly bool $can_pickup = true,
    ) {}
}
