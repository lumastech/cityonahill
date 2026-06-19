<?php

namespace App\Http\Controllers;

use App\Data\EnterScoresData;
use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;

class AssessmentScoreController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function __invoke(EnterScoresData $data): RedirectResponse
    {
        $this->resultsService->enterScores($data, auth()->id());

        return back()->with('success', 'Scores saved successfully.');
    }
}
