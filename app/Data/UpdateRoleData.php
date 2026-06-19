<?php

namespace App\Data;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateRoleData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $name,

        #[Required, IntegerType, Min(1)]
        public readonly int $level,

        #[Required, IntegerType, Min(1)]
        public readonly int $group,

        /** @var int[] */
        #[ArrayType]
        public readonly array $permission_ids = [],
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->ignore(request()->route('role')),
            ],
        ];
    }
}
