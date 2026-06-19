<?php

namespace Database\Factories;

use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermResult>
 */
class TermResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = $this->faker->numberBetween(30, 95);

        return [
            'school_id' => School::factory(),
            'pupil_id' => Pupil::factory(),
            'subject_id' => Subject::factory(),
            'term_id' => Term::factory(),
            'academic_year_id' => null,
            'stream_id' => Stream::factory(),
            'ca_marks' => null,
            'exam_marks' => null,
            'total_marks' => $total,
            'grade_letter' => TermResult::computeGradeLetter((float) $total),
            'points' => TermResult::computePoints(TermResult::computeGradeLetter((float) $total)),
            'position_in_stream' => null,
            'teacher_comment' => null,
            'published' => false,
        ];
    }
}
