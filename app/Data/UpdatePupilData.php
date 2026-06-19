<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdatePupilData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(50)]
        public readonly ?string $first_name = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $last_name = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $other_name = null,

        #[Nullable, In(['male', 'female'])]
        public readonly ?string $sex = null,

        #[Nullable, Date]
        public readonly ?string $dob = null,

        #[Nullable, StringType, Max(64)]
        public readonly ?string $nationality = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $religion = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $tribe = null,

        #[Nullable, In(['none', 'visual', 'hearing', 'physical', 'intellectual', 'other'])]
        public readonly ?string $disability = null,

        #[Nullable, StringType]
        public readonly ?string $disability_details = null,

        #[Nullable, StringType, Max(5)]
        public readonly ?string $blood_group = null,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $previous_school = null,

        #[Nullable, Date]
        public readonly ?string $date_of_admission = null,

        #[Nullable, IntegerType, Exists('grades', 'id')]
        public readonly ?int $grade_id = null,

        #[Nullable, IntegerType, Exists('streams', 'id')]
        public readonly ?int $stream_id = null,

        #[Nullable, IntegerType, Exists('academic_years', 'id')]
        public readonly ?int $academic_year_id = null,
    ) {}
}
