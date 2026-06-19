<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Mathematics', 'English Language', 'Science', 'Social Studies',
            'Civic Education', 'Religious Education', 'Physical Education',
            'Creative and Technology Studies', 'Computer Studies', 'Biology',
            'Chemistry', 'Physics', 'Geography', 'History', 'French',
        ]);

        return [
            'school_id' => School::factory(),
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 3)),
            'category' => 'core',
            'is_zambian_language' => false,
            'is_ecz_subject' => true,
        ];
    }
}
