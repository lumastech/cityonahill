<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Services\FeedingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedingReportController extends Controller
{
    public function __construct(private readonly FeedingService $feedingService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');
        $termId = $request->integer('term_id');

        $terms = Term::where('school_id', $school->id)->orderByDesc('id')->get(['id', 'name']);
        $stats = $termId ? $this->feedingService->getSchoolFeedingStats($school->id, $termId) : null;

        return Inertia::render('Feeding/Reports', [
            'terms' => $terms,
            'term_id' => $termId ?: null,
            'stats' => $stats,
        ]);
    }
}
