<?php

namespace App\Http\Controllers;

use App\Data\StoreGuardianData;
use App\Models\Guardian;
use App\Models\Pupil;
use App\Services\PupilService;
use Illuminate\Http\RedirectResponse;

class GuardianController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function store(StoreGuardianData $data, Pupil $pupil): RedirectResponse
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $this->pupilService->addGuardian($pupil->id, $data);

        return back()->with('success', 'Guardian added successfully.');
    }

    public function destroy(Pupil $pupil, Guardian $guardian): RedirectResponse
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $pupil->guardians()->detach($guardian->id);

        return back()->with('success', 'Guardian removed from pupil.');
    }
}
