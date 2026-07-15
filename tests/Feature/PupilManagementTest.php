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

it('bulk imports pupils and attaches guardians, splitting names and parsing day-first dates', function () {
    $result = $this->service->bulkImport($this->school->id, [
        'grade_id'          => $this->grade->id,
        'stream_id'         => null,
        'academic_year_id'  => $this->year->id,
        'date_of_admission' => '2025-01-15',
        'rows'              => [
            ['name' => 'Moses Muchimena', 'sex' => 'M', 'dob' => '10/05/21', 'guardian_name' => 'Moses Muchena', 'guardian_phone' => '0971 078901'],
            ['name' => 'Jean Nambaye', 'sex' => 'F', 'dob' => '07/10/22', 'guardian_name' => 'Mulenga Sarah', 'guardian_phone' => '0966 044458'],
        ],
    ]);

    expect($result['created'])->toBe(2)
        ->and($result['errors'])->toBeEmpty()
        ->and($result['skipped'])->toBeEmpty();

    $moses = Pupil::where('school_id', $this->school->id)->where('first_name', 'Moses')->first();

    expect($moses->last_name)->toBe('Muchimena')
        ->and($moses->sex->value)->toBe('male')
        ->and($moses->dob->format('Y-m-d'))->toBe('2021-05-10')
        ->and($moses->grade_id)->toBe($this->grade->id)
        ->and($moses->admission_no)->toMatch('/^MPS\/\d{4}\/\d{4}$/')
        ->and($moses->guardians()->first()->phone)->toBe('0971078901');
});

it('reports bad rows and skips duplicates without aborting the import', function () {
    $this->service->bulkImport($this->school->id, [
        'grade_id'          => $this->grade->id,
        'stream_id'         => null,
        'academic_year_id'  => $this->year->id,
        'date_of_admission' => '2025-01-15',
        'rows'              => [['name' => 'David Pemba', 'sex' => 'M', 'dob' => '24/11/20', 'guardian_name' => '', 'guardian_phone' => '']],
    ]);

    $result = $this->service->bulkImport($this->school->id, [
        'grade_id'          => $this->grade->id,
        'stream_id'         => null,
        'academic_year_id'  => $this->year->id,
        'date_of_admission' => '2025-01-15',
        'rows'              => [
            ['name' => 'David Pemba', 'sex' => 'M', 'dob' => '24/11/20', 'guardian_name' => '', 'guardian_phone' => ''], // duplicate
            ['name' => 'Nomonde', 'sex' => 'F', 'dob' => '01/01/21', 'guardian_name' => '', 'guardian_phone' => ''],       // single name
            ['name' => 'Grace Zulu', 'sex' => 'X', 'dob' => '01/01/21', 'guardian_name' => '', 'guardian_phone' => ''],    // bad sex
            ['name' => 'Ethan Daka', 'sex' => 'M', 'dob' => '20/09/22', 'guardian_name' => 'Edina Daka', 'guardian_phone' => '0771 966237'],
        ],
    ]);

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toHaveCount(1)
        ->and($result['errors'])->toHaveCount(2);
});

it('detects month-first dates and parses the whole batch consistently', function () {
    // A spreadsheet rewrote the dates as MM-DD-YY: 07-15-20 and 11-24-20 only
    // make sense month-first, which forces the ambiguous 05-10-21 to May too.
    $result = $this->service->bulkImport($this->school->id, [
        'grade_id'          => $this->grade->id,
        'stream_id'         => null,
        'academic_year_id'  => $this->year->id,
        'date_of_admission' => '2026-01-15',
        'rows'              => [
            ['name' => 'Moses Muchimena', 'sex' => 'M', 'dob' => '05-10-21', 'guardian_name' => '', 'guardian_phone' => ''],
            ['name' => 'Tariai Sande', 'sex' => 'F', 'dob' => '07-15-20', 'guardian_name' => '', 'guardian_phone' => ''],
            ['name' => 'David Pemba', 'sex' => 'M', 'dob' => '11-24-20', 'guardian_name' => '', 'guardian_phone' => ''],
        ],
    ]);

    expect($result['created'])->toBe(3)->and($result['errors'])->toBeEmpty();

    expect(Pupil::where('first_name', 'Moses')->value('dob')->format('Y-m-d'))->toBe('2021-05-10')
        ->and(Pupil::where('first_name', 'Tariai')->value('dob')->format('Y-m-d'))->toBe('2020-07-15');
});

it('does not crash on unparseable dates and reports them as row errors', function () {
    $result = $this->service->bulkImport($this->school->id, [
        'grade_id'          => $this->grade->id,
        'stream_id'         => null,
        'academic_year_id'  => $this->year->id,
        'date_of_admission' => '2026-01-15',
        'rows'              => [
            ['name' => 'Good Pupil', 'sex' => 'M', 'dob' => '20/09/22', 'guardian_name' => '', 'guardian_phone' => ''],
            ['name' => 'Bad Date', 'sex' => 'M', 'dob' => 'not-a-date', 'guardian_name' => '', 'guardian_phone' => ''],
            ['name' => 'Also Bad', 'sex' => 'F', 'dob' => '99/99/99', 'guardian_name' => '', 'guardian_phone' => ''],
        ],
    ]);

    expect($result['created'])->toBe(1)->and($result['errors'])->toHaveCount(2);
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
