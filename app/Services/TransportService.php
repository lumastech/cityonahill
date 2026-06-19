<?php

namespace App\Services;

use App\Data\AssignPupilTransportData;
use App\Data\CreateRouteData;
use App\Models\PupilTransport;
use App\Models\TransportRoute;
use Illuminate\Support\Collection;

class TransportService
{
    public function createRoute(int $schoolId, CreateRouteData $data): TransportRoute
    {
        return TransportRoute::create([
            'school_id' => $schoolId,
            'name' => $data->name,
            'pickup_points' => $data->pickup_points,
            'capacity' => $data->capacity,
            'vehicle_registration' => $data->vehicle_registration,
            'vehicle_type' => $data->vehicle_type,
            'driver_name' => $data->driver_name,
            'driver_phone' => $data->driver_phone,
        ]);
    }

    public function assignPupil(int $schoolId, AssignPupilTransportData $data): PupilTransport
    {
        $route = TransportRoute::findOrFail($data->route_id);

        $occupancy = $route->pupilTransports()->where('status', 'active')->count();

        if ($occupancy >= $route->capacity) {
            abort(422, 'Route capacity exceeded. No seats available.');
        }

        return PupilTransport::create([
            'school_id' => $schoolId,
            'pupil_id' => $data->pupil_id,
            'route_id' => $data->route_id,
            'pickup_point' => $data->pickup_point,
            'direction' => $data->direction,
            'term_id' => $data->term_id,
            'fee_amount' => $data->fee_amount,
        ]);
    }

    public function removeAssignment(int $assignmentId): void
    {
        PupilTransport::findOrFail($assignmentId)->delete();
    }

    public function getRouteManifest(int $routeId, int $termId): Collection
    {
        return PupilTransport::where('route_id', $routeId)
            ->active()
            ->forTerm($termId)
            ->with([
                'pupil:id,first_name,last_name,admission_no',
                'pupil.stream:id,name,grade_id',
                'pupil.stream.grade:id,name',
            ])
            ->orderBy('pickup_point')
            ->get();
    }

    public function getRouteSummary(int $schoolId, int $termId): array
    {
        $routes = TransportRoute::where('school_id', $schoolId)
            ->where('status', 'active')
            ->withCount(['pupilTransports as active_pupils_count' => fn ($q) => $q->active()->forTerm($termId)])
            ->get();

        $totalPupils = $routes->sum('active_pupils_count');

        $byRoute = $routes->map(fn ($r) => [
            'route_id' => $r->id,
            'route_name' => $r->name,
            'capacity' => $r->capacity,
            'pupils' => $r->active_pupils_count,
        ])->values()->all();

        return [
            'routes' => $routes,
            'total_pupils' => $totalPupils,
            'by_route' => $byRoute,
        ];
    }
}
