<?php

namespace App\Http\Controllers;

use App\Data\SendMessageData;
use App\Models\SchoolMessage;
use App\Models\User;
use App\Services\CommunicationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolMessageController extends Controller
{
    public function __construct(private readonly CommunicationService $service) {}

    public function index(): Response
    {
        $userId = auth()->id();
        $school = app('current_school');

        $threads = SchoolMessage::where('school_id', $school->id)
            ->where(fn ($q) => $q->where('sender_id', $userId)->orWhere('recipient_id', $userId))
            ->with(['sender:id,name', 'recipient:id,name', 'pupil:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn ($msg) => implode('-', array_filter([
                min($msg->sender_id, $msg->recipient_id),
                max($msg->sender_id, $msg->recipient_id),
            ])))
            ->map(fn ($msgs) => $msgs->first());

        $staff = User::whereHas('schools', fn ($q) => $q->where('school_id', $school->id))
            ->where('id', '!=', $userId)
            ->get(['id', 'name']);

        return Inertia::render('Communication/Messages/Index', [
            'threads' => $threads->values(),
            'staff' => $staff,
        ]);
    }

    public function store(SendMessageData $data): RedirectResponse
    {
        $this->service->sendMessage($data, auth()->id());

        return redirect()->route('messages.index')
            ->with('success', 'Message sent.');
    }

    public function thread(User $user): Response
    {
        $userId = auth()->id();

        $messages = $this->service->getThread($userId, $user->id);

        SchoolMessage::where('recipient_id', $userId)
            ->where('sender_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Communication/Messages/Index', [
            'thread' => $messages,
            'threadUser' => $user->only('id', 'name'),
        ]);
    }
}
