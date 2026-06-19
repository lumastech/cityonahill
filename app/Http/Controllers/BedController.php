<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Dormitory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BedController extends Controller
{
    public function store(Request $request, Dormitory $dormitory): RedirectResponse
    {
        abort_if($dormitory->school_id !== app('current_school')?->id, 403);

        $request->validate([
            'bed_number' => ['required', 'string', 'max:20'],
        ]);

        Bed::create([
            'dormitory_id' => $dormitory->id,
            'bed_number' => $request->bed_number,
        ]);

        return back()->with('success', 'Bed added.');
    }

    public function update(Request $request, Bed $bed): RedirectResponse
    {
        abort_if($bed->dormitory->school_id !== app('current_school')?->id, 403);

        $request->validate(['status' => ['required', 'in:available,occupied,maintenance']]);

        $bed->update(['status' => $request->status]);

        return back()->with('success', 'Bed status updated.');
    }

    public function destroy(Bed $bed): RedirectResponse
    {
        abort_if($bed->dormitory->school_id !== app('current_school')?->id, 403);
        $bed->delete();

        return back()->with('success', 'Bed removed.');
    }
}
