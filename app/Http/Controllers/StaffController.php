<?php

namespace App\Http\Controllers;

use App\Data\CreateStaffData;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Services\HRService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(private readonly HRService $hrService) {}

    public function index(): Response
    {
        $school = app('current_school');

        return Inertia::render('HR/Staff/Index', [
            'staff' => $this->hrService->getStaffDirectory($school->id),
        ]);
    }

    public function store(CreateStaffData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->authorize('staff.create');

        $staff = $this->hrService->createStaff($school->id, $data);

        return redirect()->route('staff.show', $staff)
            ->with('success', 'Staff member added.');
    }

    public function show(Staff $staff): Response
    {
        abort_if($staff->school_id !== app('current_school')?->id, 403);

        $staff->load([
            'user:id,name,email',
            'leaves.leaveType:id,name',
            'payrolls' => fn ($q) => $q->orderByDesc('year')->orderByDesc('month')->limit(12),
        ]);

        $leaveTypes = LeaveType::where('school_id', $staff->school_id)->get();
        $leaveBalance = [];
        foreach ($leaveTypes as $lt) {
            $leaveBalance[$lt->id] = $this->hrService->calculateLeaveBalance($staff->id, $lt->id);
        }

        return Inertia::render('HR/Staff/Show', [
            'staff' => $staff,
            'leave_types' => $leaveTypes,
            'leave_balance' => $leaveBalance,
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        abort_if($staff->school_id !== app('current_school')?->id, 403);

        $staff->update($request->only([
            'position', 'department', 'subjects_taught',
            'employment_type', 'basic_salary', 'status',
            'bank', 'bank_account', 'bank_branch',
        ]));

        return back()->with('success', 'Staff record updated.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        abort_if($staff->school_id !== app('current_school')?->id, 403);

        $staff->update(['status' => 'terminated']);

        return redirect()->route('staff.index')->with('success', 'Staff member terminated.');
    }
}
