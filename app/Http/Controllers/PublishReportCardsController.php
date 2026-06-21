<?php

namespace App\Http\Controllers;

use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublishReportCardsController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $this->authorize('report-card.publish');

        $validated = $request->validate([
            'stream_id' => ['required', 'integer', 'exists:streams,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
        ]);

        $count = $this->resultsService->publishReportCards(
            $validated['stream_id'],
            $validated['term_id']
        );

        return back()->with('success', "{$count} report cards published.");
    }
}
