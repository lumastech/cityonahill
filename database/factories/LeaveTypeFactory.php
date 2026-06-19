<?php

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => $this->faker->randomElement(['Annual Leave', 'Sick Leave', 'Study Leave', 'Maternity Leave']),
            'days_per_year' => $this->faker->randomElement([14, 21, 30]),
            'accrues' => true,
        ];
    }
}
