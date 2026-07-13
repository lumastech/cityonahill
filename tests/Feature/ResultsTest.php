<?php

use App\Data\AddReportCommentData;
use App\Data\BulkEnterTermResultsData;
use App\Data\EnterScoresData;
use App\Data\GenerateReportCardData;
use App\Data\PublishResultsData;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Grade;
use App\Models\Pupil;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ResultsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'MPS']);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 9]);
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
    $this->service = app(ResultsService::class);
});

function makePupils(int $count): Collection
{
    return Pupil::factory()->count($count)->create([
        'school_id' => test()->school->id,
        'grade_id' => test()->grade->id,
        'stream_id' => test()->stream->id,
        'academic_year_id' => test()->year->id,
        'status' => 'active',
    ]);
}

function makeAssessment(): Assessment
{
    return Assessment::create([
        'school_id' => test()->school->id,
        'stream_id' => test()->stream->id,
        'subject_id' => test()->subject->id,
        'term_id' => test()->term->id,
        'name' => 'Mid-Term Test',
        'type' => 'mid_term',
        'max_marks' => 100,
        'weight_percent' => 20,
        'date' => now()->toDateString(),
        'created_by' => test()->teacher->id,
    ]);
}

it('teacher can enter term results', function () {
    $pupils = makePupils(3);

    $data = new BulkEnterTermResultsData(
        stream_id: $this->stream->id,
        subject_id: $this->subject->id,
        term_id: $this->term->id,
        results: $pupils->map(fn ($p) => [
            'pupil_id' => $p->id,
            'ca_marks' => 60.0,
            'exam_marks' => 70.0,
            'teacher_comment' => null,
        ])->toArray(),
    );

    $results = $this->service->enterTermResults($data, $this->teacher->id);

    expect($results)->toHaveCount(3);
    expect(TermResult::where('stream_id', $this->stream->id)->count())->toBe(3);
});

it('total marks computed from ca and exam marks correctly', function () {
    $pupils = makePupils(1);

    $data = new BulkEnterTermResultsData(
        stream_id: $this->stream->id,
        subject_id: $this->subject->id,
        term_id: $this->term->id,
        results: [[
            'pupil_id' => $pupils->first()->id,
            'ca_marks' => 60.0,
            'exam_marks' => 80.0,
            'teacher_comment' => null,
        ]],
    );

    $this->service->enterTermResults($data, $this->teacher->id);

    $result = TermResult::where('pupil_id', $pupils->first()->id)->first();

    expect((float) $result->total_marks)->toBe(70.0)
        ->and($result->grade_letter)->toBe('B')
        ->and($result->points)->toBe(2);
});

it('positions computed correctly for stream', function () {
    $pupils = makePupils(3);
    $marks = [85.0, 70.0, 55.0];

    foreach ($pupils as $idx => $pupil) {
        TermResult::create([
            'school_id' => $this->school->id,
            'pupil_id' => $pupil->id,
            'subject_id' => $this->subject->id,
            'term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'stream_id' => $this->stream->id,
            'total_marks' => $marks[$idx],
            'grade_letter' => 'A',
            'points' => 1,
            'published' => 0,
        ]);
    }

    $this->service->computeStreamPositions($this->stream->id, $this->term->id);

    $results = TermResult::where('stream_id', $this->stream->id)
        ->orderByDesc('total_marks')
        ->pluck('position_in_stream')
        ->toArray();

    expect($results)->toBe([1, 2, 3]);
});

it('results not visible to parent before published', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $pupils = makePupils(1);
    $parent = User::factory()->create(['school_id' => $this->school->id]);
    $parent->assignRole('parent');

    TermResult::create([
        'school_id' => $this->school->id,
        'pupil_id' => $pupils->first()->id,
        'subject_id' => $this->subject->id,
        'term_id' => $this->term->id,
        'academic_year_id' => $this->year->id,
        'stream_id' => $this->stream->id,
        'total_marks' => 75.0,
        'grade_letter' => 'A',
        'points' => 1,
        'published' => 0,
    ]);

    $publishedResults = TermResult::published()
        ->where('pupil_id', $pupils->first()->id)
        ->get();

    expect($publishedResults)->toHaveCount(0);

    $publishData = new PublishResultsData(
        stream_id: $this->stream->id,
        term_id: $this->term->id,
    );

    $count = $this->service->publishResults($publishData, $this->teacher->id);
    expect($count)->toBe(1);

    $publishedResults = TermResult::published()
        ->where('pupil_id', $pupils->first()->id)
        ->get();

    expect($publishedResults)->toHaveCount(1);
});

it('report card generated for entire stream', function () {
    $pupils = makePupils(4);

    $data = new GenerateReportCardData(
        stream_id: $this->stream->id,
        term_id: $this->term->id,
    );

    $this->service->generateReportCards($data, $this->teacher->id);

    expect(ReportCard::where('stream_id', $this->stream->id)->count())->toBe(4);

    $card = ReportCard::where('stream_id', $this->stream->id)->first();
    expect($card->generated_at)->not->toBeNull()
        ->and($card->generated_by)->toBe($this->teacher->id);
});

it('headteacher comment added to report card', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $pupils = makePupils(1);

    $card = ReportCard::create([
        'school_id' => $this->school->id,
        'pupil_id' => $pupils->first()->id,
        'term_id' => $this->term->id,
        'academic_year_id' => $this->year->id,
        'stream_id' => $this->stream->id,
        'generated_at' => now(),
        'generated_by' => $this->teacher->id,
    ]);

    $headteacher = User::factory()->create(['school_id' => $this->school->id]);
    $headteacher->assignRole('headteacher');

    $commentData = new AddReportCommentData(
        pupil_id: $pupils->first()->id,
        term_id: $this->term->id,
        class_teacher_comment: 'A hardworking pupil.',
        headteacher_comment: 'Excellent performance this term.',
        attendance_days: 60,
        attendance_present: 57,
    );

    $this->actingAs($headteacher)
        ->put(route('report-cards.update', $card), $commentData->toArray())
        ->assertRedirect();

    $card->refresh();

    expect($card->headteacher_comment)->toBe('Excellent performance this term.')
        ->and($card->attendance_days)->toBe(60)
        ->and($card->attendance_present)->toBe(57);
});

it('answer sheets are optional when entering scores', function () {
    $pupils = makePupils(2);
    $assessment = makeAssessment();

    $scores = $this->service->enterScores(new EnterScoresData(
        assessment_id: $assessment->id,
        scores: $pupils->map(fn ($p) => ['pupil_id' => $p->id, 'marks' => 40.0])->toArray(),
    ), $this->teacher->id);

    expect($scores)->toHaveCount(2);
    expect($scores->first()->getMedia(AssessmentScore::ANSWER_SHEETS))->toHaveCount(0);
});

it('teacher can attach answer sheets to a pupil score', function () {
    Storage::fake('public');

    $pupils = makePupils(2);
    $assessment = makeAssessment();
    [$first, $second] = [$pupils->first(), $pupils->last()];

    $this->service->enterScores(new EnterScoresData(
        assessment_id: $assessment->id,
        scores: [
            [
                'pupil_id' => $first->id,
                'marks' => 55.0,
                'files' => [
                    UploadedFile::fake()->image('sheet-page-1.jpg'),
                    UploadedFile::fake()->create('sheet-page-2.pdf', 100, 'application/pdf'),
                ],
            ],
            ['pupil_id' => $second->id, 'marks' => 61.0],
        ],
    ), $this->teacher->id);

    $firstScore = AssessmentScore::where('pupil_id', $first->id)->sole();
    $secondScore = AssessmentScore::where('pupil_id', $second->id)->sole();

    expect($firstScore->getMedia(AssessmentScore::ANSWER_SHEETS)->pluck('file_name')->all())
        ->toBe(['sheet-page-1.jpg', 'sheet-page-2.pdf'])
        ->and($secondScore->getMedia(AssessmentScore::ANSWER_SHEETS))->toHaveCount(0);
});

it('answer sheets are appended to the pupil term report', function () {
    Storage::fake('public');

    $pupil = makePupils(1)->first();
    $assessment = makeAssessment();

    $this->service->enterScores(new EnterScoresData(
        assessment_id: $assessment->id,
        scores: [[
            'pupil_id' => $pupil->id,
            'marks' => 72.0,
            'files' => [UploadedFile::fake()->image('answers.jpg')],
        ]],
    ), $this->teacher->id);

    $report = $this->service->getPupilTermReport($pupil->id, $this->term->id);

    expect($report['answer_sheets'])->toHaveCount(1);

    $sheet = $report['answer_sheets']->first();

    expect($sheet['name'])->toBe('answers.jpg')
        ->and($sheet['is_image'])->toBeTrue()
        ->and($sheet['subject'])->toBe($this->subject->name)
        ->and($sheet['assessment'])->toBe($assessment->name);
});

it('answer sheets from another term are left off the report', function () {
    Storage::fake('public');

    $pupil = makePupils(1)->first();
    $otherTerm = Term::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'number' => 2,
    ]);

    $assessment = makeAssessment();

    $this->service->enterScores(new EnterScoresData(
        assessment_id: $assessment->id,
        scores: [[
            'pupil_id' => $pupil->id,
            'marks' => 72.0,
            'files' => [UploadedFile::fake()->image('answers.jpg')],
        ]],
    ), $this->teacher->id);

    $report = $this->service->getPupilTermReport($pupil->id, $otherTerm->id);

    expect($report['answer_sheets'])->toHaveCount(0);
});

it('rejects an answer sheet of an unsupported type', function () {
    Storage::fake('public');
    $this->seed(RolesAndPermissionsSeeder::class);

    $pupil = makePupils(1)->first();
    $assessment = makeAssessment();

    $teacher = User::factory()->create(['school_id' => $this->school->id]);
    $teacher->assignRole('subject-teacher');

    $this->actingAs($teacher)
        ->post(route('assessments.scores.enter', $assessment), [
            'assessment_id' => $assessment->id,
            'scores' => [[
                'pupil_id' => $pupil->id,
                'marks' => 50,
                'files' => [UploadedFile::fake()->create('malware.exe', 20)],
            ]],
        ])
        ->assertSessionHasErrors('scores.0.files.0');

    expect(AssessmentScore::where('pupil_id', $pupil->id)->first()?->getMedia(AssessmentScore::ANSWER_SHEETS) ?? collect())
        ->toHaveCount(0);
});

it('saves an answer sheet posted through the score entry route', function () {
    Storage::fake('public');
    $this->seed(RolesAndPermissionsSeeder::class);

    $pupil = makePupils(1)->first();
    $assessment = makeAssessment();

    $teacher = User::factory()->create(['school_id' => $this->school->id]);
    $teacher->assignRole('subject-teacher');

    $this->actingAs($teacher)
        ->post(route('assessments.scores.enter', $assessment), [
            'assessment_id' => $assessment->id,
            'scores' => [[
                'pupil_id' => $pupil->id,
                'marks' => 50,
                'files' => [UploadedFile::fake()->image('answers.jpg')],
            ]],
        ])
        ->assertSessionHasNoErrors();

    $score = AssessmentScore::where('pupil_id', $pupil->id)->sole();

    expect($score->getMedia(AssessmentScore::ANSWER_SHEETS))->toHaveCount(1)
        ->and($score->marks_obtained)->toEqual(50.0);
});
