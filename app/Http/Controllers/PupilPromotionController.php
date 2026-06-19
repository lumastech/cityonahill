<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Services\PupilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PupilPromotionController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function __invoke(Request $request, Stream $stream): RedirectResponse
    {
        abort_if($stream->school_id !== app('current_school')?->id, 403);

        $validated = $request->validate([
            'to_grade_id' => ['required', 'integer', 'exists:grades,id'],
            'to_stream_id' => ['nullable', 'integer', 'exists:streams,id'],
        ]);

        $count = $this->pupilService->bulkPromote(
            $stream->id,
            $validated['to_grade_id'],
            $validated['to_stream_id'] ?? null,
        );

        return back()->with('success', "{$count} pupil(s) promoted successfully.");
    }
}
