<?php

namespace App\Http\Controllers;

use App\Data\ApplyLeaveData;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Services\HRService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(private readonly HRService $hrService) {}

    public function index(): Response
    {
        $school = app('current_school');

        $leaves = Leave::where('school_id', $school->id)
            ->with(['staff.user:id,name', 'leaveType:id,name'])
            ->pending()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('HR/Leave/Approvals', [
            'pending_leaves' => $leaves,
        ]);
    }

    public function store(ApplyLeaveData $data): RedirectResponse
    {
        $school = app('current_school');
        $staff = Staff::where('school_id', $school->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $leave = $this->hrService->applyLeave($staff->id, $data);

        return back()->with('success', 'Leave application submitted.');
    }

    public function create(): Response
    {
        $school = app('current_school');
        $staff = Staff::where('school_id', $school->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $leaveTypes = LeaveType::where('school_id', $school->id)->get();
        $balance = [];
        foreach ($leaveTypes as $lt) {
            $balance[$lt->id] = $this->hrService->calculateLeaveBalance($staff->id, $lt->id);
        }

        return Inertia::render('HR/Leave/Apply', [
            'leave_types' => $leaveTypes,
            'leave_balance' => $balance,
        ]);
    }
}
