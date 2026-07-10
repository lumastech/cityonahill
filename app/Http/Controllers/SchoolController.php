<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(): Response
    {
        $school = app('current_school');
        $school?->load('headteacher:id,name');

        return Inertia::render('Schools/Index', [
            'school' => $school,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = app('current_school');

        if (! $school) {
            return redirect()->route('schools.index');
        }

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:200'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'email'   => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:200'],
        ]);

        $school->update($validated);

        return back()->with('success', 'School details updated.');
    }
}
