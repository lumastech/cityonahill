<?php

namespace App\Http\Controllers;

use App\Data\PublishResultsData;
use App\Services\ResultsService;
use Illuminate\Http\RedirectResponse;

class PublishResultsController extends Controller
{
    public function __construct(private readonly ResultsService $resultsService) {}

    public function __invoke(PublishResultsData $data): RedirectResponse
    {
        $this->authorize('results.publish');

        $count = $this->resultsService->publishResults($data, auth()->id());

        return back()->with('success', "{$count} results published.");
    }
}
