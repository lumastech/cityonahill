<?php

namespace App\Http\Controllers;

use App\Data\StoreGuardianData;
use App\Models\Guardian;
use App\Models\Pupil;
use App\Services\PupilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuardianController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $guardians = Guardian::where('school_id', $school->id)
            ->with([
                'pupils:id,first_name,last_name,admission_no',
                'portalAccess.user:id,email',
            ])
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                )
            )
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Guardians/Index', [
            'guardians' => $guardians,
            'filters'   => $request->only('search'),
        ]);
    }

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
