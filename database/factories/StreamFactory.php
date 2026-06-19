<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\School;
use App\Models\Stream;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stream>
 */
class StreamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'grade_id' => Grade::factory(),
            'name' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'capacity' => 45,
        ];
    }
}
