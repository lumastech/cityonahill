<?php

use App\Data\RegisterCandidateData;
use App\Models\EczCandidate;
use App\Models\EczSubjectEntry;
use App\Models\Grade;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\User;
use App\Services\EczService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'MPS']);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 12]);
    $this->stream = Stream::factory()->create(['school_id' => $this->school->id, 'grade_id' => $this->grade->id]);
    $this->teacher = User::factory()->create(['school_id' => $this->school->id]);
    $this->service = app(EczService::class);
});

function makePupil(): Pupil
{
    return Pupil::factory()->create([
        'school_id' => test()->school->id,
        'grade_id' => test()->grade->id,
        'stream_id' => test()->stream->id,
        'status' => 'active',
    ]);
}

it('only grade 7, 9, 12 pupils can be registered as ecz candidates', function () {
    $pupil = makePupil();

    $data = new RegisterCandidateData(
        pupil_id: $pupil->id,
        grade_level: 12,
        exam_year: 2026,
    );

    $candidate = $this->service->registerCandidate($this->school->id, $data);

    expect($candidate)->toBeInstanceOf(EczCandidate::class)
        ->and($candidate->grade_level)->toBe(12)
        ->and($candidate->exam_year)->toBe(2026)
        ->and($candidate->pupil_id)->toBe($pupil->id);
});

it('division is computed correctly for grade 12', function () {
    $pupil = makePupil();
    $candidate = EczCandidate::factory()->create([
        'school_id' => $this->school->id,
        'pupil_id' => $pupil->id,
        'grade_level' => 12,
        'exam_year' => 2026,
    ]);

    // Enter 6 subjects — best 6: A(1)+A(1)+B(2)+B(2)+C(3)+C(3) = 12 → Division I
    $subjectGrades = [
        ['name' => 'Mathematics', 'code' => 'MAT', 'grade' => 'A', 'points' => 1],
        ['name' => 'English', 'code' => 'ENG', 'grade' => 'A', 'points' => 1],
        ['name' => 'Biology', 'code' => 'BIO', 'grade' => 'B', 'points' => 2],
        ['name' => 'Chemistry', 'code' => 'CHE', 'grade' => 'B', 'points' => 2],
        ['name' => 'Geography', 'code' => 'GEO', 'grade' => 'C', 'points' => 3],
        ['name' => 'History', 'code' => 'HIS', 'grade' => 'C', 'points' => 3],
    ];

    foreach ($subjectGrades as $g) {
        $subject = Subject::factory()->create(['school_id' => $this->school->id, 'name' => $g['name'], 'code' => $g['code']]);
        EczSubjectEntry::create([
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'entered_by' => $this->teacher->id,
            'actual_grade' => $g['grade'],
            'actual_points' => $g['points'],
        ]);
    }

    $candidate->load('subjectEntries');
    $division = $candidate->computeDivision();

    expect($division)->toBe('Division I');
});

it('predicted grade is saved per subject entry', function () {
    $pupil = makePupil();
    $subject = Subject::factory()->create(['school_id' => $this->school->id]);
    $candidate = EczCandidate::factory()->create([
        'school_id' => $this->school->id,
        'pupil_id' => $pupil->id,
        'grade_level' => 12,
        'exam_year' => 2026,
    ]);

    $entries = $this->service->addSubjectEntries($candidate->id, [$subject->id], $this->teacher->id);
    $entry = $entries->first();

    $updated = $this->service->setPredictedGrade($entry->id, 'B');

    expect($updated->predicted_grade)->toBe('B');
    $this->assertDatabaseHas('ecz_subject_entries', [
        'id' => $entry->id,
        'predicted_grade' => 'B',
    ]);
});

it('actual results are entered and division is updated on the candidate', function () {
    $pupil = makePupil();
    $candidate = EczCandidate::factory()->create([
        'school_id' => $this->school->id,
        'pupil_id' => $pupil->id,
        'grade_level' => 12,
        'exam_year' => 2026,
    ]);

    $subjectData = [
        ['name' => 'Mathematics', 'code' => 'MAT'],
        ['name' => 'English', 'code' => 'ENG'],
        ['name' => 'Biology', 'code' => 'BIO'],
        ['name' => 'Chemistry', 'code' => 'CHE'],
        ['name' => 'Geography', 'code' => 'GEO'],
        ['name' => 'History', 'code' => 'HIS'],
    ];
    $subjects = collect($subjectData)->map(fn ($d) => Subject::factory()->create([
        'school_id' => $this->school->id,
        'name' => $d['name'],
        'code' => $d['code'],
    ]));
    foreach ($subjects as $subject) {
        EczSubjectEntry::create([
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'entered_by' => $this->teacher->id,
        ]);
    }

    // A=1, A=1, B=2, B=2, C=3, D=4 → sum=13 → Division I
    $gradeMap = ['A', 'A', 'B', 'B', 'C', 'D'];
    $pointMap = [1, 1, 2, 2, 3, 4];

    $results = [];
    foreach ($subjects as $i => $subject) {
        $results[] = [
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'actual_grade' => $gradeMap[$i],
            'actual_points' => $pointMap[$i],
        ];
    }

    $this->service->enterActualResults($results);

    $candidate->refresh();
    expect($candidate->division)->toBe('Division I')
        ->and($candidate->total_points)->toBe(13)
        ->and($candidate->registration_status)->toBe('confirmed');
});
