<?php

use App\Data\CreatePortalAccountData;
use App\Mail\GuardianWelcomeMail;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\GuardianPortalAccess;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ParentPortalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->school = School::factory()->create(['code' => 'MPS']);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 9]);
    $this->stream = Stream::factory()->create(['school_id' => $this->school->id, 'grade_id' => $this->grade->id]);
    $this->service = app(ParentPortalService::class);
});

function makeGuardianWithPupil(): array
{
    $guardian = Guardian::factory()->create(['school_id' => test()->school->id]);
    $pupil = Pupil::factory()->create([
        'school_id' => test()->school->id,
        'grade_id' => test()->grade->id,
        'stream_id' => test()->stream->id,
        'status' => 'active',
    ]);
    $guardian->pupils()->attach($pupil->id, ['is_primary' => true, 'is_emergency' => false, 'can_pickup' => true]);

    return compact('guardian', 'pupil');
}

it('portal account creation sends welcome email', function () {
    Mail::fake();

    ['guardian' => $guardian] = makeGuardianWithPupil();

    $data = CreatePortalAccountData::from([
        'guardian_id' => $guardian->id,
        'email' => 'parent@example.com',
    ]);

    $user = $this->service->createPortalAccount($data, 'TempPass123!');

    expect($user->email)->toBe('parent@example.com')
        ->and($user->hasRole('parent'))->toBeTrue();

    $this->assertDatabaseHas('guardian_portal_access', [
        'guardian_id' => $guardian->id,
        'user_id' => $user->id,
    ]);

    Mail::assertSent(GuardianWelcomeMail::class, fn ($mail) => $mail->hasTo('parent@example.com'));
});

it('parent can only view own child data', function () {
    ['guardian' => $guardian, 'pupil' => $pupil] = makeGuardianWithPupil();

    $parentUser = User::factory()->create(['school_id' => $this->school->id, 'is_parent' => true]);
    $parentUser->assignRole('parent');

    GuardianPortalAccess::create([
        'guardian_id' => $guardian->id,
        'user_id' => $parentUser->id,
        'activated_at' => now(),
    ]);

    $summary = $this->service->getChildSummary($parentUser->id, $pupil->id);

    expect($summary['pupil']->id)->toBe($pupil->id);
});

it('parent cannot view another guardians child', function () {
    ['guardian' => $guardian] = makeGuardianWithPupil();

    // A different pupil not linked to this guardian
    $otherPupil = Pupil::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'status' => 'active',
    ]);

    $parentUser = User::factory()->create(['school_id' => $this->school->id, 'is_parent' => true]);
    $parentUser->assignRole('parent');

    GuardianPortalAccess::create([
        'guardian_id' => $guardian->id,
        'user_id' => $parentUser->id,
        'activated_at' => now(),
    ]);

    expect(fn () => $this->service->getChildSummary($parentUser->id, $otherPupil->id))
        ->toThrow(HttpException::class);
});

it('parent sees only published results', function () {
    ['guardian' => $guardian, 'pupil' => $pupil] = makeGuardianWithPupil();

    $parentUser = User::factory()->create(['school_id' => $this->school->id, 'is_parent' => true]);
    $parentUser->assignRole('parent');

    GuardianPortalAccess::create([
        'guardian_id' => $guardian->id,
        'user_id' => $parentUser->id,
        'activated_at' => now(),
    ]);

    $year = AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
    $term = Term::factory()->create([
        'school_id' => $this->school->id,
        'is_current' => true,
        'number' => 1,
        'academic_year_id' => $year->id,
    ]);

    $subject = Subject::factory()->create([
        'school_id' => $this->school->id,
        'name' => 'Mathematics',
        'code' => 'MAT',
    ]);

    $sharedFields = [
        'school_id' => $this->school->id,
        'pupil_id' => $pupil->id,
        'term_id' => $term->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $year->id,
    ];

    // One published result, one draft (published=0)
    TermResult::factory()->create([
        ...$sharedFields,
        'subject_id' => $subject->id,
        'total_marks' => 72,
        'grade_letter' => 'B',
        'published' => 1,
    ]);
    TermResult::factory()->create([
        ...$sharedFields,
        'subject_id' => Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'English', 'code' => 'ENG'])->id,
        'total_marks' => 60,
        'grade_letter' => 'C',
        'published' => 0,
    ]);

    $summary = $this->service->getChildSummary($parentUser->id, $pupil->id);

    expect($summary['latest_results'])->toHaveCount(1)
        ->and($summary['latest_results']->first()->grade_letter)->toBe('B');
});
