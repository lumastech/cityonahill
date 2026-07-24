<?php

use App\Models\Grade;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectLearningContent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->school = School::factory()->create(['code' => 'MPS']);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 5]);
    $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);

    $this->admin = User::factory()->create(['school_id' => $this->school->id]);
    $this->admin->assignRole('school-admin');
});

it('staff can add learning content with media to a subject', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('subjects.contents.store', $this->subject), [
            'title' => 'Photosynthesis',
            'body' => 'How plants make food.',
            'grade_id' => $this->grade->id,
            'files' => [
                UploadedFile::fake()->image('diagram.png'),
                UploadedFile::fake()->create('notes.pdf', 200, 'application/pdf'),
            ],
        ])
        ->assertRedirect();

    $content = SubjectLearningContent::sole();

    expect($content->subject_id)->toBe($this->subject->id)
        ->and($content->grade_id)->toBe($this->grade->id)
        ->and($content->getMedia(SubjectLearningContent::MEDIA))->toHaveCount(2);
});

it('rejects a learning material of an unsupported type', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('subjects.contents.store', $this->subject), [
            'title' => 'Bad upload',
            'files' => [UploadedFile::fake()->create('script.exe', 10)],
        ])
        ->assertSessionHasErrors('files.0');

    expect(SubjectLearningContent::count())->toBe(0);
});

it('staff can delete learning content', function () {
    $content = SubjectLearningContent::create([
        'school_id' => $this->school->id,
        'subject_id' => $this->subject->id,
        'title' => 'Removable',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('subjects.contents.destroy', [$this->subject, $content]))
        ->assertRedirect();

    expect(SubjectLearningContent::count())->toBe(0);
});

it('learning content media can be served and deleted', function () {
    Storage::fake('public');

    $content = SubjectLearningContent::create([
        'school_id' => $this->school->id,
        'subject_id' => $this->subject->id,
        'title' => 'With media',
    ]);
    $media = $content->addMedia(UploadedFile::fake()->image('slide.png'))
        ->toMediaCollection(SubjectLearningContent::MEDIA);

    $this->actingAs($this->admin)
        ->get(route('subject-contents.media.show', [$content, $media]))
        ->assertOk();

    $this->actingAs($this->admin)
        ->delete(route('subject-contents.media.destroy', [$content, $media]))
        ->assertRedirect();

    expect($content->fresh()->getMedia(SubjectLearningContent::MEDIA))->toHaveCount(0);
});

it('content of a subject from another school is not accessible', function () {
    $otherSchool = School::factory()->create(['code' => 'OTH']);
    $otherSubject = Subject::factory()->create(['school_id' => $otherSchool->id]);

    $this->actingAs($this->admin)
        ->post(route('subjects.contents.store', $otherSubject), [
            'title' => 'Should fail',
        ])
        ->assertForbidden();

    expect(SubjectLearningContent::count())->toBe(0);
});
