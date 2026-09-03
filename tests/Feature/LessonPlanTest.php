<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\LessonPlan;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->school = School::factory()->create(['code' => 'MPS']);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 8]);
    $this->year = AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
    $this->term = Term::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'number' => 1,
        'is_current' => true,
    ]);
    $this->stream = Stream::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);
    $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);

    $this->teacher = User::factory()->create(['school_id' => $this->school->id]);
    $this->teacher->assignRole('subject-teacher');

    $this->headteacher = User::factory()->create(['school_id' => $this->school->id]);
    $this->headteacher->assignRole('headteacher');
});

function lessonPlanPayload(array $overrides = []): array
{
    return array_merge([
        'subject_id' => test()->subject->id,
        'stream_id' => test()->stream->id,
        'term_id' => test()->term->id,
        'topic' => 'Fractions',
        'sub_topic' => 'Numerators and denominators',
        'general_competence' => 'Demonstrate understanding of numbers.',
        'specific_competence' => 'Identify parts of a fraction.',
        'lesson_goal' => 'Understand fractions.',
        'reference' => 'MOE syllabus, Grade 8 Mathematics, p. 42',
        'prior_knowledge' => 'Learners can divide whole numbers.',
        'learning_material' => 'Fraction charts, chalkboard.',
        'learning_environment' => 'Classroom',
        'stages' => [
            ['stage' => 'Introduction', 'teacher_activity' => 'Reviews division.', 'learner_activity' => 'Answer questions.', 'assessment_criteria' => 'Recalls division.'],
            ['stage' => 'Development', 'teacher_activity' => 'Introduces numerators and denominators.', 'learner_activity' => 'Label fraction parts.', 'assessment_criteria' => 'Names both parts.'],
            ['stage' => 'Application', 'teacher_activity' => 'Sets shading exercise.', 'learner_activity' => 'Shade fractions.', 'assessment_criteria' => 'Shades correctly.'],
        ],
        'conclusion' => 'Recap the two parts of a fraction and set homework.',
        'evaluation' => 'Goal met; three learners still confuse the denominator.',
        'week_number' => 3,
        'lesson_date' => '2026-09-03',
        'duration_minutes' => 40,
        'boys_count' => 18,
        'girls_count' => 22,
        'submit' => false,
    ], $overrides);
}

it('teacher can save a lesson plan as a draft', function () {
    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload(['submit' => false]))
        ->assertRedirect(route('lesson-plans.index'));

    $plan = LessonPlan::sole();

    expect($plan->status)->toBe('draft')
        ->and($plan->submitted_by)->toBe($this->teacher->id)
        ->and($plan->submitted_at)->toBeNull();
});

it('teacher can submit a lesson plan for approval', function () {
    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload(['submit' => true]))
        ->assertRedirect();

    $plan = LessonPlan::sole();

    expect($plan->status)->toBe('submitted')
        ->and($plan->submitted_at)->not->toBeNull();
});

it('teacher can attach files to a lesson plan', function () {
    Storage::fake('public');

    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload([
            'attachments' => [
                UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
            ],
        ]))
        ->assertSessionHasNoErrors();

    $plan = LessonPlan::sole();

    expect($plan->getMedia(LessonPlan::ATTACHMENTS))->toHaveCount(1);
});

it('rejects an attachment of an unsupported type', function () {
    Storage::fake('public');

    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload([
            'attachments' => [UploadedFile::fake()->create('malware.exe', 10)],
        ]))
        ->assertSessionHasErrors('attachments.0');

    expect(LessonPlan::count())->toBe(0);
});

it('headteacher can approve a submitted lesson plan', function () {
    $plan = LessonPlan::create(lessonPlanPayload([
        'school_id' => $this->school->id,
        'status' => 'submitted',
        'submitted_by' => $this->teacher->id,
        'submitted_at' => now(),
    ]));

    $this->actingAs($this->headteacher)
        ->post(route('lesson-plans.review', $plan), ['status' => 'approved'])
        ->assertRedirect();

    $plan->refresh();

    expect($plan->status)->toBe('approved')
        ->and($plan->reviewed_by)->toBe($this->headteacher->id)
        ->and($plan->reviewed_at)->not->toBeNull();
});

it('rejecting a lesson plan requires a comment', function () {
    $plan = LessonPlan::create(lessonPlanPayload([
        'school_id' => $this->school->id,
        'status' => 'submitted',
        'submitted_by' => $this->teacher->id,
    ]));

    $this->actingAs($this->headteacher)
        ->post(route('lesson-plans.review', $plan), ['status' => 'rejected'])
        ->assertSessionHasErrors('comment');

    expect($plan->fresh()->status)->toBe('submitted');
});

it('teacher can resubmit a rejected lesson plan', function () {
    $plan = LessonPlan::create(lessonPlanPayload([
        'school_id' => $this->school->id,
        'status' => 'rejected',
        'submitted_by' => $this->teacher->id,
        'reviewed_by' => $this->headteacher->id,
        'reviewed_at' => now(),
        'comment' => 'Add more detail.',
    ]));

    $this->actingAs($this->teacher)
        ->put(route('lesson-plans.update', $plan), lessonPlanPayload([
            'lesson_goal' => 'Understand and compare fractions.',
            'submit' => true,
        ]))
        ->assertRedirect();

    $plan->refresh();

    expect($plan->status)->toBe('submitted')
        ->and($plan->comment)->toBeNull()
        ->and($plan->reviewed_by)->toBeNull();
});

it('a teacher without approve permission cannot review', function () {
    $plan = LessonPlan::create(lessonPlanPayload([
        'school_id' => $this->school->id,
        'status' => 'submitted',
        'submitted_by' => $this->teacher->id,
    ]));

    $other = User::factory()->create(['school_id' => $this->school->id]);
    $other->assignRole('subject-teacher');

    $this->actingAs($other)
        ->post(route('lesson-plans.review', $plan), ['status' => 'approved'])
        ->assertForbidden();
});

it('an approved lesson plan can no longer be edited by its author', function () {
    $plan = LessonPlan::create(lessonPlanPayload([
        'school_id' => $this->school->id,
        'status' => 'approved',
        'submitted_by' => $this->teacher->id,
    ]));

    $this->actingAs($this->teacher)
        ->put(route('lesson-plans.update', $plan), lessonPlanPayload(['topic' => 'Changed']))
        ->assertForbidden();
});

it('a lesson plan from another school is not accessible', function () {
    $otherSchool = School::factory()->create(['code' => 'OTH']);
    $plan = LessonPlan::create(lessonPlanPayload([
        'school_id' => $otherSchool->id,
        'status' => 'submitted',
        'submitted_by' => $this->teacher->id,
    ]));

    $this->actingAs($this->headteacher)
        ->post(route('lesson-plans.review', $plan), ['status' => 'approved'])
        ->assertForbidden();
});

it('stores the lesson plan template fields including the stage table', function () {
    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload())
        ->assertSessionHasNoErrors();

    $plan = LessonPlan::sole();

    expect($plan->topic)->toBe('Fractions')
        ->and($plan->sub_topic)->toBe('Numerators and denominators')
        ->and($plan->general_competence)->toBe('Demonstrate understanding of numbers.')
        ->and($plan->specific_competence)->toBe('Identify parts of a fraction.')
        ->and($plan->lesson_goal)->toBe('Understand fractions.')
        ->and($plan->reference)->toBe('MOE syllabus, Grade 8 Mathematics, p. 42')
        ->and($plan->prior_knowledge)->toBe('Learners can divide whole numbers.')
        ->and($plan->learning_material)->toBe('Fraction charts, chalkboard.')
        ->and($plan->learning_environment)->toBe('Classroom')
        ->and($plan->duration_minutes)->toBe(40)
        ->and($plan->total_pupils)->toBe(40)
        ->and($plan->conclusion)->toBe('Recap the two parts of a fraction and set homework.')
        ->and($plan->evaluation)->toBe('Goal met; three learners still confuse the denominator.')
        ->and($plan->stages)->toHaveCount(3)
        ->and($plan->stages[0]['stage'])->toBe('Introduction')
        ->and($plan->stages[2]['assessment_criteria'])->toBe('Shades correctly.');
});

it('drops stage rows the teacher left completely blank', function () {
    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload([
            'stages' => [
                ['stage' => 'Introduction', 'teacher_activity' => 'Reviews division.'],
                ['stage' => '', 'teacher_activity' => '', 'learner_activity' => '', 'assessment_criteria' => ''],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $plan = LessonPlan::sole();

    expect($plan->stages)->toHaveCount(1)
        ->and($plan->stages[0]['learner_activity'])->toBeNull();
});

it('requires the competences and lesson goal', function () {
    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload([
            'general_competence' => '',
            'specific_competence' => '',
            'lesson_goal' => '',
        ]))
        ->assertSessionHasErrors(['general_competence', 'specific_competence', 'lesson_goal']);

    expect(LessonPlan::count())->toBe(0);
});

it('offers each class with its roll so the form can fill the pupil stats', function () {
    Pupil::factory()->count(3)->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $this->year->id,
        'sex' => 'male',
    ]);
    Pupil::factory()->count(2)->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $this->year->id,
        'sex' => 'female',
    ]);
    // A pupil who has left should not be counted.
    Pupil::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $this->year->id,
        'sex' => 'female',
        'status' => 'transferred',
    ]);

    $response = $this->actingAs($this->teacher)->get(route('lesson-plans.create'));

    $response->assertOk();

    $stream = collect($response->viewData('page')['props']['streams'])
        ->firstWhere('id', $this->stream->id);

    expect($stream['boys_count'])->toBe(3)
        ->and($stream['girls_count'])->toBe(2);
});

it('accepts a plan written before teaching, with no evaluation yet', function () {
    $this->actingAs($this->teacher)
        ->post(route('lesson-plans.store'), lessonPlanPayload([
            'conclusion' => null,
            'evaluation' => null,
            'submit' => true,
        ]))
        ->assertSessionHasNoErrors();

    $plan = LessonPlan::sole();

    expect($plan->status)->toBe('submitted')
        ->and($plan->conclusion)->toBeNull()
        ->and($plan->evaluation)->toBeNull();
});
