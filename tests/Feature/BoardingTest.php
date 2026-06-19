<?php

use App\Data\AllocateBedData;
use App\Data\CreateDormitoryData;
use App\Models\AcademicYear;
use App\Models\Bed;
use App\Models\Dormitory;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use App\Services\BoardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'BRD']);
    $this->service = app(BoardingService::class);
    $this->user = User::factory()->create();

    $this->academicYear = AcademicYear::factory()->create([
        'school_id' => $this->school->id,
        'name' => '2025',
    ]);
    $this->term = Term::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    // Create a male and female dormitory
    $this->maleDorm = $this->service->createDormitory($this->school->id, CreateDormitoryData::from([
        'name' => 'Chileshe House',
        'gender' => 'male',
        'capacity' => 10,
    ]));

    $this->femaleDorm = $this->service->createDormitory($this->school->id, CreateDormitoryData::from([
        'name' => 'Mwape House',
        'gender' => 'female',
        'capacity' => 10,
    ]));

    // Add one bed to each dorm
    $this->maleBed = Bed::create(['dormitory_id' => $this->maleDorm->id, 'bed_number' => 'M-01']);
    $this->femaleBed = Bed::create(['dormitory_id' => $this->femaleDorm->id, 'bed_number' => 'F-01']);
});

it('male pupil cannot be allocated to female dormitory', function () {
    $malePupil = Pupil::factory()->create([
        'school_id' => $this->school->id,
        'sex' => 'male',
    ]);

    $data = AllocateBedData::from([
        'pupil_id' => $malePupil->id,
        'bed_id' => $this->femaleBed->id,
        'term_id' => $this->term->id,
        'fee_amount' => 500,
    ]);

    expect(fn () => $this->service->allocateBed($this->school->id, $data))
        ->toThrow(HttpException::class);

    // Verify bed is still available
    expect($this->femaleBed->fresh()->status)->toBe('available');
});

it('occupied bed cannot be allocated to another pupil', function () {
    $firstPupil = Pupil::factory()->create([
        'school_id' => $this->school->id,
        'sex' => 'male',
    ]);

    $firstData = AllocateBedData::from([
        'pupil_id' => $firstPupil->id,
        'bed_id' => $this->maleBed->id,
        'term_id' => $this->term->id,
        'fee_amount' => 500,
    ]);

    $this->service->allocateBed($this->school->id, $firstData);

    expect($this->maleBed->fresh()->status)->toBe('occupied');

    $secondPupil = Pupil::factory()->create([
        'school_id' => $this->school->id,
        'sex' => 'male',
    ]);

    $secondData = AllocateBedData::from([
        'pupil_id' => $secondPupil->id,
        'bed_id' => $this->maleBed->id,
        'term_id' => $this->term->id,
        'fee_amount' => 500,
    ]);

    expect(fn () => $this->service->allocateBed($this->school->id, $secondData))
        ->toThrow(HttpException::class);
});
