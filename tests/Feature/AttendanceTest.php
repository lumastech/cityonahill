<?php

use App\Data\BulkAttendanceData;
use App\Data\OpenAttendanceSessionData;
use App\Exceptions\ConflictException;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Grade;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\Term;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'TST']);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 5]);
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
    $this->service = app(AttendanceService::class);
});

it('class teacher can open and record attendance', function () {
    $pupils = Pupil::factory()->count(3)->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    $data = new OpenAttendanceSessionData(
        stream_id: $this->stream->id,
        term_id: $this->term->id,
        date: '2025-03-10',
        session_type: 'full_day',
    );

    $session = $this->service->openSession($this->school->id, $data);

    expect($session)->toBeInstanceOf(AttendanceSession::class)
        ->and($session->stream_id)->toBe($this->stream->id)
        ->and($session->finalized)->toBeFalse();

    $records = AttendanceRecord::where('session_id', $session->id)->get();
    expect($records)->toHaveCount(3);

    $bulkData = new BulkAttendanceData(
        session_id: $session->id,
        records: $pupils->map(fn ($p) => [
            'pupil_id' => $p->id,
            'status' => 'present',
            'remarks' => null,
        ])->toArray(),
    );

    $teacher = User::factory()->create(['school_id' => $this->school->id]);
    $updated = $this->service->recordAttendance($bulkData, $teacher->id);

    expect($updated->every(fn ($r) => $r->status === 'present'))->toBeTrue();

    $session->refresh();
    expect($session->finalized)->toBeTrue();
});

it('duplicate session for same stream and date is rejected', function () {
    $data = new OpenAttendanceSessionData(
        stream_id: $this->stream->id,
        term_id: $this->term->id,
        date: '2025-03-10',
        session_type: 'full_day',
    );

    $this->service->openSession($this->school->id, $data);

    expect(fn () => $this->service->openSession($this->school->id, $data))
        ->toThrow(ConflictException::class);
});

it('term attendance percentage calculated correctly', function () {
    $pupil = Pupil::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    $statuses = ['present', 'present', 'absent', 'late'];

    foreach ($statuses as $i => $status) {
        $session = AttendanceSession::factory()->create([
            'school_id' => $this->school->id,
            'stream_id' => $this->stream->id,
            'term_id' => $this->term->id,
            'date' => '2025-03-0'.($i + 1),
            'finalized' => true,
        ]);

        AttendanceRecord::create([
            'session_id' => $session->id,
            'pupil_id' => $pupil->id,
            'status' => $status,
        ]);
    }

    $summary = $this->service->getTermSummary($pupil->id, $this->term->id);

    expect($summary['total_days'])->toBe(4)
        ->and($summary['present'])->toBe(2)
        ->and($summary['absent'])->toBe(1)
        ->and($summary['late'])->toBe(1)
        ->and($summary['percentage'])->toBe(75.0);
});

it('absent pupils auto-populated when session opened', function () {
    Pupil::factory()->count(5)->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    Pupil::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
        'stream_id' => $this->stream->id,
        'academic_year_id' => $this->year->id,
        'status' => 'withdrawn',
    ]);

    $data = new OpenAttendanceSessionData(
        stream_id: $this->stream->id,
        term_id: $this->term->id,
        date: '2025-03-10',
        session_type: 'full_day',
    );

    $session = $this->service->openSession($this->school->id, $data);

    $records = AttendanceRecord::where('session_id', $session->id)->get();

    expect($records)->toHaveCount(5)
        ->and($records->every(fn ($r) => $r->status === 'absent'))->toBeTrue();
});
