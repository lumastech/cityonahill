<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Decimal;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateStaffData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('users', 'id')]
        public readonly int $user_id,

        #[Required, StringType, Max(30)]
        public readonly string $employee_no,

        #[Required, In([
            'headteacher', 'deputy_headteacher', 'class_teacher', 'subject_teacher',
            'bursar', 'librarian', 'boarding_master', 'transport_coordinator',
            'feeding_coordinator', 'admin', 'support', 'counsellor',
        ])]
        public readonly string $position,

        #[Required, In(['permanent', 'contract', 'temporary', 'volunteer'])]
        public readonly string $employment_type,

        #[Required, Date]
        public readonly string $employment_date,

        #[Required, Decimal(0, 2)]
        public readonly float $basic_salary,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $department = null,

        #[Nullable]
        public readonly ?array $subjects_taught = null,

        #[Nullable, StringType, Max(25)]
        public readonly ?string $napsa_no = null,

        #[Nullable, StringType, Max(15)]
        public readonly ?string $tpin = null,
    ) {}
}
