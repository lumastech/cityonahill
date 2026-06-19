<?php

namespace App\Http\Controllers;

use App\Data\SendSmsData;
use App\Models\Grade;
use App\Models\SmsLog;
use App\Services\CommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SmsController extends Controller
{
    public function __construct(private readonly CommunicationService $service) {}

    public function compose(): Response
    {
        $school = app('current_school');

        $grades = Grade::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Communication/SMS/Compose', [
            'grades' => $grades,
        ]);
    }

    public function send(SendSmsData $data): RedirectResponse
    {
        $school = app('current_school');

        gate()->authorize('sendSms', $school);

        $this->service->sendSms($school->id, $data);

        return redirect()->route('sms.log')
            ->with('success', 'SMS queued for delivery.');
    }

    public function log(Request $request): Response
    {
        $school = app('current_school');

        $logs = SmsLog::where('school_id', $school->id)
            ->when($request->status, fn ($q) => $q->forStatus($request->status))
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('Communication/SMS/Log', [
            'logs' => $logs,
        ]);
    }

    public function __invoke(SendSmsData $data): RedirectResponse
    {
        return $this->send($data);
    }
}
