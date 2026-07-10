<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (School::exists()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Setup/Wizard', [
            'provinces' => config('zssms.zambia_provinces'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (School::exists()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:200'],
            ...SchoolController::rules(),
        ]);

        Setting::updateOrCreate(
            ['id' => 'org_name'],
            ['name' => 'Organization Name', 'value' => $validated['organization_name']]
        );

        $school = School::create([
            ...collect($validated)->except('organization_name')->all(),
            'owner_id' => $request->user()->id,
            'status'   => 'active',
        ]);

        $request->session()->put('school_id', $school->id);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Setup complete — your first branch is ready.');
    }
}
