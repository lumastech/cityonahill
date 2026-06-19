<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' School',
            'code' => strtoupper($this->faker->unique()->lexify('SCH????')),
            'type' => $this->faker->randomElement(['government', 'private', 'mission', 'grant-aided']),
            'level' => $this->faker->randomElement(['primary', 'secondary', 'basic', 'combined']),
            'province' => $this->faker->randomElement([
                'Lusaka', 'Copperbelt', 'Central', 'Eastern', 'Northern',
                'North-Western', 'Southern', 'Western', 'Luapula', 'Muchinga',
            ]),
            'district' => $this->faker->city(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'established_year' => $this->faker->numberBetween(1950, 2020),
            'status' => 'active',
        ];
    }
}
