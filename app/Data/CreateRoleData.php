<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CreateRoleData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100), Unique('roles', 'name')]
        public readonly string $name,

        #[Required, IntegerType, Min(1)]
        public readonly int $level,

        #[Required, IntegerType, Min(1)]
        public readonly int $group,

        /** @var int[] */
        #[ArrayType]
        public readonly array $permission_ids = [],
    ) {}
}
