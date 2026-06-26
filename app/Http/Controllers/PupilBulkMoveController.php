<?php

namespace App\Http\Controllers;

use App\Models\Pupil;
use App\Models\Stream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PupilBulkMoveController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $school = app('current_school');

        abort_unless(
            auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'headteacher', 'deputy-headteacher', 'class-teacher']),
            403
        );

        $validated = $request->validate([
            'pupil_ids'   => ['required', 'array', 'min:1'],
            'pupil_ids.*' => ['integer', 'exists:pupils,id'],
            'stream_id'   => ['required', 'integer', 'exists:streams,id'],
        ]);

        $stream = Stream::findOrFail($validated['stream_id']);

        $count = Pupil::whereIn('id', $validated['pupil_ids'])
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->update([
                'stream_id' => $stream->id,
                'grade_id'  => $stream->grade_id,
            ]);

        return back()->with('success', "{$count} " . str('pupil')->plural($count) . " moved to {$stream->grade->name} — {$stream->name}.");
    }
}
