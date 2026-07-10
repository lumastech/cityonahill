<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(): Response
    {
        $schools = School::with('headteacher:id,name')
            ->withCount(['pupils', 'staff'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Schools/Index', [
            'schools'         => $schools,
            'currentSchoolId' => app('current_school')?->id,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Schools/Create', [
            'provinces' => config('zssms.zambia_provinces'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(self::rules());

        $school = School::create([
            ...$validated,
            'status' => 'active',
        ]);

        return redirect()
            ->route('schools.show', $school)
            ->with('success', 'Branch created.');
    }

    public function show(School $school): Response
    {
        $school->load('headteacher:id,name')->loadCount(['pupils', 'staff']);

        return Inertia::render('Schools/Show', [
            'school' => $school,
        ]);
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate(self::rules($school));

        $school->update($validated);

        return back()->with('success', 'Branch details updated.');
    }

    public function destroy(School $school): RedirectResponse
    {
        if ($school->pupils()->exists() || $school->staff()->exists()) {
            return back()->with('error', 'This branch has pupils or staff and cannot be deleted. Mark it inactive instead.');
        }

        $school->delete();

        return redirect()
            ->route('schools.index')
            ->with('success', 'Branch deleted.');
    }

    /** @return array<string, mixed> */
    public static function rules(?School $school = null): array
    {
        return [
            'name'                => ['required', 'string', 'max:200'],
            'code'                => ['required', 'string', 'max:20', Rule::unique('schools', 'code')->ignore($school)],
            'type'                => ['required', Rule::in(['government', 'private', 'mission', 'grant-aided'])],
            'level'               => ['required', Rule::in(['primary', 'secondary', 'basic', 'combined'])],
            'province'            => ['required', Rule::in(config('zssms.zambia_provinces'))],
            'district'            => ['required', 'string', 'max:50'],
            'address'             => ['required', 'string', 'max:500'],
            'phone'               => ['required', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:100'],
            'website'             => ['nullable', 'url', 'max:200'],
            'moe_registration_no' => ['nullable', 'string', 'max:50', Rule::unique('schools', 'moe_registration_no')->ignore($school)],
            'established_year'    => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
        ];
    }
}
