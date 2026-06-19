<?php

namespace App\Http\Controllers;

use App\Data\CreateRouteData;
use App\Models\Term;
use App\Models\TransportRoute;
use App\Services\TransportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransportRouteController extends Controller
{
    public function __construct(private readonly TransportService $transportService) {}

    public function index(): Response
    {
        $school = app('current_school');
        $termId = request()->integer('term_id');

        $routes = TransportRoute::where('school_id', $school->id)
            ->withCount(['pupilTransports as occupancy' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $terms = Term::where('school_id', $school->id)->orderByDesc('id')->get(['id', 'name']);

        return Inertia::render('Transport/Routes/Index', [
            'routes' => $routes,
            'terms' => $terms,
        ]);
    }

    public function store(CreateRouteData $data): RedirectResponse
    {
        $school = app('current_school');
        $route = $this->transportService->createRoute($school->id, $data);

        return redirect()->route('transport-routes.show', $route->id)
            ->with('success', 'Route created.');
    }

    public function show(TransportRoute $transportRoute): Response
    {
        abort_if($transportRoute->school_id !== app('current_school')?->id, 403);

        $termId = request()->integer('term_id');
        $terms = Term::where('school_id', $transportRoute->school_id)->orderByDesc('id')->get(['id', 'name']);

        $manifest = $termId
            ? $this->transportService->getRouteManifest($transportRoute->id, $termId)
            : collect();

        return Inertia::render('Transport/Routes/Show', [
            'route' => $transportRoute,
            'terms' => $terms,
            'term_id' => $termId ?: null,
            'manifest' => $manifest,
        ]);
    }

    public function destroy(TransportRoute $transportRoute): RedirectResponse
    {
        abort_if($transportRoute->school_id !== app('current_school')?->id, 403);
        $transportRoute->delete();

        return redirect()->route('transport-routes.index')->with('success', 'Route deleted.');
    }
}
