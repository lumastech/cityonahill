<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class SubmitSchoolApplicationData extends Data
{
    public function __construct(
        #[Required, StringType, Max(200)]
        public readonly string $school_name,

        #[Required, StringType, Max(63)]
        public readonly string $subdomain,

        #[Required, In(['day', 'boarding', 'day_and_boarding'])]
        public readonly string $type,

        #[Required, In(['primary', 'secondary', 'combined'])]
        public readonly string $level,

        #[Required, StringType, Max(100)]
        public readonly string $province,

        #[Required, StringType, Max(100)]
        public readonly string $district,

        #[Required, StringType, Max(20)]
        public readonly string $contact_phone,

        #[Required, Email, Max(150)]
        public readonly string $contact_email,

        #[Required, StringType, Max(200)]
        public readonly string $headteacher_name,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $moe_registration_no = null,

        #[Nullable, ArrayType]
        public readonly ?array $modules_config = null,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $mobile_money_number = null,
    ) {}
}
