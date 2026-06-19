<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AddSubjectEntryData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('subjects', 'id')]
        public readonly int $subject_id,

        #[Nullable, In(['A', 'B', 'C', 'D', 'F'])]
        public readonly ?string $predicted_grade = null,
    ) {}
}
