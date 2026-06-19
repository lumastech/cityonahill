<?php

namespace App\Http\Controllers;

use App\Data\AssignPupilTransportData;
use App\Models\PupilTransport;
use App\Services\TransportService;
use Illuminate\Http\RedirectResponse;

class PupilTransportController extends Controller
{
    public function __construct(private readonly TransportService $transportService) {}

    public function store(AssignPupilTransportData $data): RedirectResponse
    {
        $school = app('current_school');
        $this->transportService->assignPupil($school->id, $data);

        return back()->with('success', 'Pupil assigned to route.');
    }

    public function destroy(PupilTransport $pupilTransport): RedirectResponse
    {
        abort_if($pupilTransport->school_id !== app('current_school')?->id, 403);
        $this->transportService->removeAssignment($pupilTransport->id);

        return back()->with('success', 'Assignment removed.');
    }
}
