<?php

namespace App\Http\Controllers;

use App\Models\TransportRoute;
use App\Services\TransportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RouteManifestController extends Controller
{
    public function __construct(private readonly TransportService $transportService) {}

    public function __invoke(Request $request, TransportRoute $transportRoute): Response
    {
        abort_if($transportRoute->school_id !== app('current_school')?->id, 403);

        $termId = $request->integer('term_id');

        $manifest = $termId
            ? $this->transportService->getRouteManifest($transportRoute->id, $termId)
            : collect();

        return Inertia::render('Transport/Manifest/Print', [
            'route' => $transportRoute,
            'manifest' => $manifest,
            'term_id' => $termId ?: null,
        ]);
    }
}
