<?php

namespace App\Http\Controllers;

use App\Data\OpenFeedingSessionData;
use App\Data\RecordFeedingData;
use App\Models\FeedingSession;
use App\Models\Pupil;
use App\Models\Stream;
use App\Services\FeedingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeedingSessionController extends Controller
{
    public function __construct(private readonly FeedingService $feedingService) {}

    public function index(): Response
    {
        $school = app('current_school');

        $sessions = FeedingSession::where('school_id', $school->id)
            ->with('stream:id,name')
            ->withCount(['feedingRecords as served_count' => fn ($q) => $q->where('served', 1)])
            ->orderByDesc('date')
            ->orderBy('meal_type')
            ->paginate(30)
            ->withQueryString();

        $streams = Stream::where('school_id', $school->id)
            ->with('grade:id,name,grade_number')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_id']);

        return Inertia::render('Feeding/DailyRegister', [
            'sessions' => $sessions,
            'streams' => $streams,
        ]);
    }

    public function store(OpenFeedingSessionData $data): RedirectResponse
    {
        $school = app('current_school');
        $session = $this->feedingService->openSession($school->id, $data, auth()->id());

        return redirect()->route('feeding-sessions.show', $session->id)
            ->with('success', 'Session opened.');
    }

    public function show(FeedingSession $feedingSession): Response
    {
        abort_if($feedingSession->school_id !== app('current_school')?->id, 403);

        $feedingSession->load('stream:id,name,grade_id', 'stream.grade:id,name');

        $pupils = $feedingSession->stream_id
            ? Pupil::where('stream_id', $feedingSession->stream_id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no'])
            : Pupil::where('school_id', $feedingSession->school_id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no']);

        $servedIds = $feedingSession->feedingRecords()->where('served', 1)->pluck('pupil_id')->toArray();

        return Inertia::render('Feeding/DailyRegister', [
            'session' => $feedingSession,
            'pupils' => $pupils,
            'served_ids' => $servedIds,
            'streams' => collect(),
        ]);
    }

    public function update(RecordFeedingData $data, FeedingSession $feedingSession): RedirectResponse
    {
        abort_if($feedingSession->school_id !== app('current_school')?->id, 403);

        $this->feedingService->recordFeeding($data);

        return back()->with('success', 'Feeding records saved.');
    }

    public function destroy(FeedingSession $feedingSession): RedirectResponse
    {
        abort_if($feedingSession->school_id !== app('current_school')?->id, 403);
        $this->feedingService->finalizeSession($feedingSession->id);

        return back()->with('success', 'Session finalized.');
    }
}
