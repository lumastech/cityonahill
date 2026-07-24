<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\LessonPlan;
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
        'title' => 'Fractions',
        'objectives' => 'Understand fractions.',
        'content' => 'Introduce numerators and denominators.',
        'week_number' => 3,
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
            'content' => 'Expanded content with worked examples.',
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
        ->put(route('lesson-plans.update', $plan), lessonPlanPayload(['title' => 'Changed']))
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
