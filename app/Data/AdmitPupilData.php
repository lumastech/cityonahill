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

class AdmitPupilData extends Data
{
    public function __construct(
        #[Required, StringType, Max(50)]
        public readonly string $first_name,

        #[Required, StringType, Max(50)]
        public readonly string $last_name,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $other_name,

        #[Required, In(['male', 'female'])]
        public readonly string $sex,

        #[Required, Date]
        public readonly string $dob,

        #[StringType, Max(64)]
        public readonly string $nationality,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $religion,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $tribe,

        #[In(['none', 'visual', 'hearing', 'physical', 'intellectual', 'other'])]
        public readonly string $disability,

        #[Nullable, StringType]
        public readonly ?string $disability_details,

        #[Nullable, StringType, Max(5)]
        public readonly ?string $blood_group,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $previous_school,

        #[Required, Date]
        public readonly string $date_of_admission,

        #[Required, IntegerType, Exists('grades', 'id')]
        public readonly int $grade_id,

        #[Nullable, IntegerType, Exists('streams', 'id')]
        public readonly ?int $stream_id,

        #[Required, IntegerType, Exists('academic_years', 'id')]
        public readonly int $academic_year_id,
    ) {}
}
