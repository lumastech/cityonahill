<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateNoticeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(200)]
        public readonly string $title,

        #[Required, StringType]
        public readonly string $content,

        #[Required, In(['all', 'parents', 'staff', 'pupils', 'grade'])]
        public readonly string $target_audience = 'all',

        #[Nullable, IntegerType, Exists('grades', 'id')]
        public readonly ?int $target_grade_id = null,

        #[Nullable]
        public readonly ?string $published_at = null,

        #[Nullable]
        public readonly ?string $expires_at = null,
    ) {}
}
