<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2020, 2030);

        return [
            'school_id' => School::factory(),
            'name' => "{$year}",
            'start_date' => "{$year}-01-15",
            'end_date' => "{$year}-12-05",
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(['is_current' => true]);
    }
}
