<?php

namespace App\Http\Controllers;

use App\Data\ApproveLeaveData;
use App\Models\Leave;
use App\Services\HRService;
use Illuminate\Http\RedirectResponse;

class ApproveLeaveController extends Controller
{
    public function __construct(private readonly HRService $hrService) {}

    public function __invoke(ApproveLeaveData $data, int $leave): RedirectResponse
    {
        $leaveModel = Leave::findOrFail($leave);
        abort_if($leaveModel->school_id !== app('current_school')?->id, 403);

        $this->authorize('leave.approve');

        $this->hrService->approveLeave($leaveModel->id, $data, auth()->id());

        return back()->with('success', 'Leave '.$data->status.'.');
    }
}
