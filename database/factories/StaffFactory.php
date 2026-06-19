<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'school_id' => School::factory(),
            'employee_no' => strtoupper($this->faker->bothify('EMP-####')),
            'position' => $this->faker->randomElement(['class_teacher', 'subject_teacher', 'admin', 'bursar']),
            'department' => null,
            'subjects_taught' => null,
            'employment_type' => $this->faker->randomElement(['permanent', 'contract']),
            'employment_date' => $this->faker->date(),
            'end_date' => null,
            'basic_salary' => $this->faker->randomFloat(2, 3_000, 20_000),
            'napsa_no' => null,
            'tpin' => null,
            'status' => 'active',
        ];
    }
}
