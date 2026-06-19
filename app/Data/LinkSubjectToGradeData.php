<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class LinkSubjectToGradeData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('subjects', 'id')]
        public readonly int $subject_id,

        #[Required, IntegerType, Exists('grades', 'id')]
        public readonly int $grade_id,

        #[BooleanType]
        public readonly bool $is_core = true,

        #[Required, IntegerType, Min(0), Max(100)]
        public readonly int $ca_weight = 40,

        #[Required, IntegerType, Min(0), Max(100)]
        public readonly int $exam_weight = 60,
    ) {}

    public static function rules(): array
    {
        return [
            'ca_weight' => [
                new class implements \Illuminate\Contracts\Validation\ValidationRule
                {
                    public function validate(string $attribute, mixed $value, \Closure $fail): void
                    {
                        $ca = (int) request()->integer('ca_weight');
                        $exam = (int) request()->integer('exam_weight');

                        if ($ca + $exam !== 100) {
                            $fail('CA weight and exam weight must sum to 100.');
                        }
                    }
                },
            ],
        ];
    }
}
