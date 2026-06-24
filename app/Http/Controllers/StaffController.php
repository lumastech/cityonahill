<?php

namespace App\Http\Controllers;

use App\Data\CreateStaffData;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\User;
use App\Services\HRService;
use Spatie\Permission\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

    public function create(): Response
    {
        $school = app('current_school');

        $existingUserIds = Staff::where('school_id', $school->id)->pluck('user_id');

        return Inertia::render('HR/Staff/Create', [
            'available_users' => User::whereNotIn('id', $existingUserIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'subjects' => Subject::where('school_id', $school->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'roles' => Role::whereNotIn('name', ['super-admin', 'parent'])
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = app('current_school');

        $this->authorize('staff.create');

        $validated = $request->validate([
            'mode'             => ['required', 'in:existing,new'],
            'user_id'          => ['required_if:mode,existing', 'nullable', 'integer', 'exists:users,id'],
            'name'             => ['required_if:mode,new', 'nullable', 'string', 'max:200'],
            'email'            => ['required_if:mode,new', 'nullable', 'email', 'unique:users,email'],
            'employee_no'      => ['required', 'string', 'max:30'],
            'position'         => ['required', 'string', Rule::exists('roles', 'name')->whereNotIn('name', ['super-admin', 'parent'])],
            'employment_type'  => ['required', 'string'],
            'employment_date'  => ['required', 'date'],
            'basic_salary'     => ['required', 'numeric', 'min:0'],
            'department'       => ['nullable', 'string', 'max:100'],
            'subjects_taught'  => ['nullable', 'array'],
            'napsa_no'         => ['nullable', 'string', 'max:25'],
            'tpin'             => ['nullable', 'string', 'max:15'],
        ]);

        if ($validated['mode'] === 'new') {
            $user = User::create([
                'name'               => $validated['name'],
                'email'              => $validated['email'],
                'password'           => Hash::make(Str::random(12)),
                'email_verified_at'  => now(),
            ]);
            $userId = $user->id;
        } else {
            $userId = $validated['user_id'];
        }

        $data = CreateStaffData::from([
            'user_id'         => $userId,
            'employee_no'     => $validated['employee_no'],
            'position'        => $validated['position'],
            'employment_type' => $validated['employment_type'],
            'employment_date' => $validated['employment_date'],
            'basic_salary'    => $validated['basic_salary'],
            'department'      => $validated['department'] ?? null,
            'subjects_taught' => $validated['subjects_taught'] ?? null,
            'napsa_no'        => $validated['napsa_no'] ?? null,
            'tpin'            => $validated['tpin'] ?? null,
        ]);

        $staff = $this->hrService->createStaff($school->id, $data);

        return redirect()->route('staff.show', $staff)
            ->with('success', 'Staff member added.');
    }

    public function show(Staff $staff): Response
    {
        abort_if($staff->school_id !== app('current_school')?->id, 403);

        $user = auth()->user();
        $canViewAll = $user->hasAnyRole([
            'super-admin', 'school-admin', 'headteacher', 'deputy-headteacher', 'finance-officer', 'hr-officer',
        ]);
        $isSelf = $staff->user_id === $user->id;

        abort_if(! $canViewAll && ! $isSelf, 403);

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
            'staff'         => $staff,
            'leave_types'   => $leaveTypes,
            'leave_balance' => $leaveBalance,
            'subjects'      => Subject::where('school_id', $staff->school_id)->orderBy('name')->get(['id', 'name']),
            'roles'         => Role::whereNotIn('name', ['super-admin', 'parent'])->orderBy('name')->pluck('name'),
            'can_edit'      => $canViewAll,
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        abort_if($staff->school_id !== app('current_school')?->id, 403);

        $oldPosition = $staff->position;

        $staff->update($request->only([
            'position', 'department', 'subjects_taught',
            'employment_type', 'basic_salary', 'status',
            'bank', 'bank_account', 'bank_branch',
        ]));

        if ($request->filled('position') && $staff->position !== $oldPosition) {
            $user = $staff->user;
            if ($user) {
                $this->hrService->syncPositionRole($user, $staff->position);
            }
        }

        return back()->with('success', 'Staff record updated.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        abort_if($staff->school_id !== app('current_school')?->id, 403);

        $staff->update(['status' => 'terminated']);

        return redirect()->route('staff.index')->with('success', 'Staff member terminated.');
    }
}
