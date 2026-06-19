<?php

use App\Data\AssignPupilTransportData;
use App\Data\CreateRouteData;
use App\Models\AcademicYear;
use App\Models\Pupil;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use App\Services\TransportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'TRP']);
    $this->service = app(TransportService::class);
    $this->user = User::factory()->create();

    $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id]);
    $this->term = Term::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $routeData = CreateRouteData::from([
        'name' => 'Route A — Lusaka North',
        'pickup_points' => ['Northmead', 'Kabwata', 'Town Centre'],
        'capacity' => 3,
    ]);

    $this->route = $this->service->createRoute($this->school->id, $routeData);
});

it('pupil assigned to route and manifest updated', function () {
    $pupil = Pupil::factory()->create(['school_id' => $this->school->id]);

    $data = AssignPupilTransportData::from([
        'pupil_id' => $pupil->id,
        'route_id' => $this->route->id,
        'pickup_point' => 'Northmead',
        'direction' => 'both',
        'term_id' => $this->term->id,
        'fee_amount' => 150.00,
    ]);

    $assignment = $this->service->assignPupil($this->school->id, $data);

    expect($assignment->pupil_id)->toBe($pupil->id)
        ->and($assignment->route_id)->toBe($this->route->id)
        ->and($assignment->pickup_point)->toBe('Northmead');

    $manifest = $this->service->getRouteManifest($this->route->id, $this->term->id);

    expect($manifest)->toHaveCount(1)
        ->and($manifest->first()->pupil_id)->toBe($pupil->id);
});

it('capacity exceeded assignment rejected', function () {
    // Fill route to capacity (3)
    $pupils = Pupil::factory()->count(3)->create(['school_id' => $this->school->id]);

    foreach ($pupils as $pupil) {
        $data = AssignPupilTransportData::from([
            'pupil_id' => $pupil->id,
            'route_id' => $this->route->id,
            'pickup_point' => 'Kabwata',
            'direction' => 'both',
            'term_id' => $this->term->id,
            'fee_amount' => 0,
        ]);
        $this->service->assignPupil($this->school->id, $data);
    }

    // 4th pupil should be rejected
    $extra = Pupil::factory()->create(['school_id' => $this->school->id]);

    $overData = AssignPupilTransportData::from([
        'pupil_id' => $extra->id,
        'route_id' => $this->route->id,
        'pickup_point' => 'Town Centre',
        'direction' => 'both',
        'term_id' => $this->term->id,
        'fee_amount' => 0,
    ]);

    expect(fn () => $this->service->assignPupil($this->school->id, $overData))
        ->toThrow(HttpException::class);
});
