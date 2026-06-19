<?php

namespace App\Http\Controllers;

use App\Models\Pupil;
use App\Models\TermResult;
use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComputeCAMarksController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stream_id' => ['required', 'integer', 'exists:streams,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
        ]);

        $pupils = Pupil::where('stream_id', $validated['stream_id'])
            ->where('status', 'active')
            ->pluck('id');

        foreach ($pupils as $pupilId) {
            $ca = $this->resultsService->computeCAMarks(
                $pupilId,
                $validated['subject_id'],
                $validated['term_id']
            );

            TermResult::updateOrCreate(
                [
                    'pupil_id' => $pupilId,
                    'subject_id' => $validated['subject_id'],
                    'term_id' => $validated['term_id'],
                ],
                ['ca_marks' => $ca]
            );
        }

        return back()->with('success', 'CA marks computed from assessments.');
    }
}
