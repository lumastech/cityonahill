<?php

use App\Data\ApplyLeaveData;
use App\Data\ApproveLeaveData;
use App\Data\GeneratePayrollData;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use App\Services\HRService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'ZSS']);
    $this->user = User::factory()->create(['school_id' => $this->school->id]);
    $this->service = app(HRService::class);

    $this->staff = Staff::factory()->create([
        'school_id' => $this->school->id,
        'user_id' => $this->user->id,
        'employee_no' => 'EMP-001',
        'basic_salary' => 10_000,
        'status' => 'active',
    ]);

    $this->leaveType = LeaveType::factory()->create([
        'school_id' => $this->school->id,
        'name' => 'Annual Leave',
        'days_per_year' => 21,
    ]);
});

it('staff member can apply for leave', function () {
    $data = new ApplyLeaveData(
        leave_type_id: $this->leaveType->id,
        start_date: '2026-07-07',
        end_date: '2026-07-11',
        reason: 'Family vacation',
    );

    $leave = $this->service->applyLeave($this->staff->id, $data);

    expect($leave)->toBeInstanceOf(Leave::class)
        ->and($leave->staff_id)->toBe($this->staff->id)
        ->and($leave->leave_type_id)->toBe($this->leaveType->id)
        ->and($leave->status)->toBe('pending')
        ->and($leave->total_days)->toBe(5); // Mon–Fri
});

it('leave balance deducted on approval', function () {
    $leave = Leave::create([
        'school_id' => $this->school->id,
        'staff_id' => $this->staff->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => '2026-07-07',
        'end_date' => '2026-07-11',
        'total_days' => 5,
        'reason' => 'Test',
        'status' => 'pending',
    ]);

    $balanceBefore = $this->service->calculateLeaveBalance($this->staff->id, $this->leaveType->id);

    $approveData = new ApproveLeaveData(status: 'approved', comment: null);
    $this->service->approveLeave($leave->id, $approveData, $this->user->id);

    $balanceAfter = $this->service->calculateLeaveBalance($this->staff->id, $this->leaveType->id);

    expect($balanceBefore)->toBe(21)
        ->and($balanceAfter)->toBe(16); // 21 - 5
});

it('NAPSA and PAYE calculated correctly', function () {
    // Basic salary 10,000 ZMW
    // NAPSA employee = 10,000 * 5% = 500
    // PAYE: 0-4800=0, 4800-9200=25%=1100, 9200-10000=30%=240 => total 1340
    // Net pay = 10000 - 500 - 1340 = 8160

    $data = new GeneratePayrollData(month: 6, year: 2026, include_all_staff: true);
    $payrolls = $this->service->generatePayroll($this->school->id, $data);

    expect($payrolls)->toHaveCount(1);

    $payroll = $payrolls->first();
    expect($payroll->napsa_employee)->toEqual(500.00)
        ->and($payroll->napsa_employer)->toEqual(500.00)
        ->and($payroll->paye)->toEqual(1340.00)
        ->and($payroll->net_pay)->toEqual(8160.00);
});

it('payroll cannot be generated twice for same month and year', function () {
    $data = new GeneratePayrollData(month: 6, year: 2026, include_all_staff: true);

    $this->service->generatePayroll($this->school->id, $data);
    $this->service->generatePayroll($this->school->id, $data);

    expect(Payroll::where('school_id', $this->school->id)
        ->where('staff_id', $this->staff->id)
        ->where('month', 6)
        ->where('year', 2026)
        ->count()
    )->toBe(1);
});
