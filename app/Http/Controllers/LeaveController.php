<?php

namespace App\Http\Controllers;

use App\Data\ApplyLeaveData;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Services\HRService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(private readonly HRService $hrService) {}

    public function index(Request $request): Response
    {
        $school  = app('current_school');
        $status  = $request->string('status')->toString() ?: 'pending';
        $search  = $request->string('search')->toString() ?: null;

        $query = Leave::where('school_id', $school->id)
            ->with(['staff.user:id,name', 'leaveType:id,name'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('staff.user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        return Inertia::render('HR/Leave/Approvals', [
            'leaves'  => $query->paginate(30)->withQueryString(),
            'filters' => compact('status', 'search'),
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

    public function create(): Response|RedirectResponse
    {
        $school = app('current_school');
        $staff = Staff::where('school_id', $school->id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $staff) {
            return redirect()->route('leaves.index')
                ->with('error', 'You must be a registered staff member to apply for leave.');
        }

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
