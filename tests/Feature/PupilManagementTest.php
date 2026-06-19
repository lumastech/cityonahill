<?php

use App\Data\AdmitPupilData;
use App\Data\StoreGuardianData;
use App\Data\TransferPupilData;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Stream;
use App\Models\User;
use App\Services\PupilService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeAdmitData(int $gradeId, int $academicYearId): AdmitPupilData
{
    return new AdmitPupilData(
        first_name: 'John',
        last_name: 'Banda',
        other_name: null,
        sex: 'male',
        dob: '2015-03-10',
        nationality: 'Zambian',
        religion: null,
        tribe: null,
        disability: 'none',
        disability_details: null,
        blood_group: null,
        previous_school: null,
        date_of_admission: '2025-01-15',
        grade_id: $gradeId,
        stream_id: null,
        academic_year_id: $academicYearId,
    );
}

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'MPS']);
    $this->grade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 1]);
    $this->year = AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
    $this->service = app(PupilService::class);
});

it('admits a pupil and generates a formatted admission number', function () {
    $data = makeAdmitData($this->grade->id, $this->year->id);

    $pupil = $this->service->admit($this->school->id, $data);

    expect($pupil->first_name)->toBe('John')
        ->and($pupil->admission_no)->toMatch('/^MPS\/\d{4}\/\d{4}$/')
        ->and($pupil->status)->toBe('active');
});

it('can add a primary guardian to a pupil and enforces single primary', function () {
    $data = makeAdmitData($this->grade->id, $this->year->id);
    $pupil = $this->service->admit($this->school->id, $data);

    $guardianData = new StoreGuardianData(
        first_name: 'Mary',
        last_name: 'Banda',
        relationship: 'mother',
        phone: '0977000001',
        phone2: null,
        email: null,
        nrc: null,
        occupation: null,
        employer: null,
        address: null,
        is_primary: true,
        is_emergency: true,
        can_pickup: true,
    );

    $guardian = $this->service->addGuardian($pupil->id, $guardianData);

    $pupil->refresh()->load('guardians');

    expect($guardian->first_name)->toBe('Mary')
        ->and($pupil->guardians)->toHaveCount(1)
        ->and($pupil->guardians->first()->pivot->is_primary)->toBe(1);
});

it('external transfer sets pupil status to transferred and records history', function () {
    $data = makeAdmitData($this->grade->id, $this->year->id);
    $pupil = $this->service->admit($this->school->id, $data);

    $user = User::factory()->create(['school_id' => $this->school->id]);

    $transferData = new TransferPupilData(
        type: 'external',
        to_school: 'Kabulonga Boys Secondary',
        transfer_date: '2025-04-30',
        reason: 'Family relocation',
    );

    $this->service->transfer($pupil->id, $transferData, $user->id);

    $pupil->refresh();

    expect($pupil->status)->toBe('transferred')
        ->and($pupil->transfer_school)->toBe('Kabulonga Boys Secondary')
        ->and($pupil->transfers()->count())->toBe(1);
});

it('bulk promote updates all active pupils in a stream', function () {
    $stream = Stream::factory()->create([
        'school_id' => $this->school->id,
        'grade_id' => $this->grade->id,
    ]);

    $targetGrade = Grade::factory()->create(['school_id' => $this->school->id, 'grade_number' => 2]);

    foreach (range(1, 3) as $i) {
        $admitData = makeAdmitData($this->grade->id, $this->year->id);
        $pupil = $this->service->admit($this->school->id, $admitData);
        $pupil->update(['stream_id' => $stream->id]);
    }

    // Add one withdrawn pupil that should not be promoted
    $skipped = makeAdmitData($this->grade->id, $this->year->id);
    $withdrawnPupil = $this->service->admit($this->school->id, $skipped);
    $withdrawnPupil->update(['stream_id' => $stream->id, 'status' => 'withdrawn']);

    $count = $this->service->bulkPromote($stream->id, $targetGrade->id, null);

    expect($count)->toBe(3);

    $promoted = Pupil::where('stream_id', $stream->id)->where('status', 'active')->get();
    foreach ($promoted as $p) {
        expect($p->grade_id)->toBe($targetGrade->id);
    }
});

it('parent cannot admit pupils via HTTP', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $parent = User::factory()->create(['school_id' => $this->school->id]);
    $parent->assignRole('parent');

    $this->actingAs($parent)
        ->post(route('pupils.store'), [
            'first_name' => 'Test',
            'last_name' => 'Pupil',
            'sex' => 'male',
            'dob' => '2015-01-01',
            'date_of_admission' => '2025-01-15',
            'grade_id' => $this->grade->id,
            'academic_year_id' => $this->year->id,
            'nationality' => 'Zambian',
            'disability' => 'none',
        ])
        ->assertForbidden();
});
