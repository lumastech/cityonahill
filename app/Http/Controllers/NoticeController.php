<?php

namespace App\Http\Controllers;

use App\Data\CreateNoticeData;
use App\Models\Grade;
use App\Models\Notice;
use App\Services\CommunicationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    public function __construct(private readonly CommunicationService $service) {}

    public function index(): Response
    {
        $school = app('current_school');

        $notices = Notice::where('school_id', $school->id)
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Communication/Notices/Index', [
            'notices' => $notices,
        ]);
    }

    public function create(): Response
    {
        $grades = Grade::where('school_id', app('current_school')->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Communication/Notices/Create', [
            'grades' => $grades,
        ]);
    }

    public function store(CreateNoticeData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->service->createNotice($school->id, $data, auth()->id());

        return redirect()->route('notices.index')
            ->with('success', 'Notice created successfully.');
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $notice->delete();

        return redirect()->route('notices.index')
            ->with('success', 'Notice deleted.');
    }
}
