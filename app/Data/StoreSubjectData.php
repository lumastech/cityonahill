<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreSubjectData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $name,

        #[Required, StringType]
        public readonly string $code,

        #[Required, In(['core', 'elective', 'language', 'vocational', 'religious', 'physical'])]
        public readonly string $category,

        #[BooleanType]
        public readonly bool $is_zambian_language = false,

        #[BooleanType]
        public readonly bool $is_ecz_subject = false,

        #[Nullable, StringType]
        public readonly ?string $description = null,
    ) {}
}
