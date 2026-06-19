<?php

namespace Database\Factories;

use App\Models\EczCandidate;
use App\Models\Pupil;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EczCandidate>
 */
class EczCandidateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'pupil_id' => Pupil::factory(),
            'exam_year' => now()->year,
            'grade_level' => $this->faker->randomElement([7, 9, 12]),
            'index_number' => null,
            'centre_number' => null,
            'registration_status' => 'pending',
            'division' => null,
            'total_points' => null,
        ];
    }
}
