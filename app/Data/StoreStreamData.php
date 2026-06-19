<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreStreamData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('grades', 'id')]
        public readonly int $grade_id,

        #[Required, StringType, Max(20)]
        public readonly string $name,

        #[Nullable, IntegerType, Exists('users', 'id')]
        public readonly ?int $class_teacher_id,

        #[IntegerType, Min(1), Max(100)]
        public readonly int $capacity = 45,

        #[Nullable, IntegerType]
        public readonly ?int $academic_year_id = null,
    ) {}
}
