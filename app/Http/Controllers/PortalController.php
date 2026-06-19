<?php

namespace App\Http\Controllers;

use App\Models\Pupil;
use App\Services\ParentPortalService;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function __construct(private readonly ParentPortalService $portalService) {}

    public function dashboard(): Response
    {
        $children = $this->portalService->getChildrenForParent(auth()->id());

        return Inertia::render('Parent/Dashboard', [
            'children' => $children->load('grade:id,name,grade_number', 'stream:id,name'),
        ]);
    }

    public function childDetail(Pupil $pupil): Response
    {
        $summary = $this->portalService->getChildSummary(auth()->id(), $pupil->id);

        return Inertia::render('Parent/Child/Results', [
            'summary' => $summary,
        ]);
    }
}
