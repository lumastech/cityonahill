<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;
        $gradeNumber = (($sequence - 1) % 12) + 1;

        $level = match (true) {
            $gradeNumber <= 7 => 'primary',
            $gradeNumber <= 9 => 'junior_secondary',
            default => 'senior_secondary',
        };

        return [
            'school_id' => School::factory(),
            'name' => "Grade {$gradeNumber}",
            'grade_number' => $gradeNumber,
            'level' => $level,
            'is_ecz_year' => in_array($gradeNumber, [7, 9, 12]),
            'order_index' => $gradeNumber,
        ];
    }
}
