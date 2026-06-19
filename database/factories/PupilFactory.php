<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Pupil;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pupil>
 */
class PupilFactory extends Factory
{
    public function definition(): array
    {
        $year = $this->faker->year();

        return [
            'school_id' => School::factory(),
            'admission_no' => strtoupper($this->faker->lexify('???')).'/'.$year.'/'.$this->faker->numerify('####'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'other_name' => null,
            'sex' => $this->faker->randomElement(['male', 'female']),
            'dob' => $this->faker->dateTimeBetween('-16 years', '-5 years')->format('Y-m-d'),
            'nationality' => 'Zambian',
            'disability' => 'none',
            'date_of_admission' => $this->faker->dateTimeThisYear()->format('Y-m-d'),
            'grade_id' => Grade::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'status' => 'active',
        ];
    }
}
