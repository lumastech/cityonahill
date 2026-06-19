<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\School;
use App\Models\Stream;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'stream_id' => Stream::factory(),
            'term_id' => Term::factory(),
            'date' => $this->faker->dateTimeThisYear()->format('Y-m-d'),
            'session_type' => 'full_day',
            'recorded_by' => null,
            'finalized' => false,
        ];
    }

    public function finalized(): static
    {
        return $this->state(['finalized' => true]);
    }
}
