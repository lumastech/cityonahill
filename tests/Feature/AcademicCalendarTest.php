<?php

use App\Data\CreateAcademicYearData;
use App\Data\CreateTermData;
use App\Exceptions\ConflictException;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->user = User::factory()->create(['school_id' => $this->school->id]);
    $this->service = app(CalendarService::class);
});

it('creates an academic year and sets it as current, deactivating previous', function () {
    $first = AcademicYear::factory()->create([
        'school_id' => $this->school->id,
        'name' => '2025',
        'is_current' => true,
    ]);

    $data = new CreateAcademicYearData(
        name: '2026',
        start_date: '2026-01-14',
        end_date: '2026-12-04',
        is_current: true,
    );

    $new = $this->service->createAcademicYear($this->school->id, $data);

    expect($new->is_current)->toBeTrue()
        ->and($new->name)->toBe('2026');

    expect($first->fresh()->is_current)->toBeFalse();
});

it('prevents adding more than 3 terms to an academic year', function () {
    $year = AcademicYear::factory()->create(['school_id' => $this->school->id]);

    foreach ([1, 2, 3] as $n) {
        Term::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'number' => $n,
            'name' => "Term {$n}",
        ]);
    }

    $data = new CreateTermData(
        academic_year_id: $year->id,
        name: 'Term 4',
        number: 1,
        start_date: '2025-11-01',
        end_date: '2025-12-05',
    );

    expect(fn () => $this->service->createTerm($this->school->id, $data))
        ->toThrow(ConflictException::class);
});

it('sets a term as current and deactivates all others for the school', function () {
    $year = AcademicYear::factory()->create(['school_id' => $this->school->id]);

    $activeTerm = Term::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $year->id,
        'number' => 1,
        'name' => 'Term 1',
        'is_current' => true,
    ]);

    $nextTerm = Term::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $year->id,
        'number' => 2,
        'name' => 'Term 2',
        'is_current' => false,
    ]);

    $this->service->setCurrentTerm($this->school->id, $nextTerm);

    expect($activeTerm->fresh()->is_current)->toBeFalse()
        ->and($nextTerm->fresh()->is_current)->toBeTrue();
});
