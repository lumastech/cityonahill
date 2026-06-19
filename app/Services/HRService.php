<?php

namespace App\Services;

use App\Data\ApplyLeaveData;
use App\Data\ApproveLeaveData;
use App\Data\CreateStaffData;
use App\Data\GeneratePayrollData;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Staff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HRService
{
    public function createStaff(int $schoolId, CreateStaffData $data): Staff
    {
        return Staff::create([
            'school_id' => $schoolId,
            'user_id' => $data->user_id,
            'employee_no' => $data->employee_no,
            'position' => $data->position,
            'department' => $data->department,
            'subjects_taught' => $data->subjects_taught,
            'employment_type' => $data->employment_type,
            'employment_date' => $data->employment_date,
            'basic_salary' => $data->basic_salary,
            'napsa_no' => $data->napsa_no,
            'tpin' => $data->tpin,
            'status' => 'active',
        ]);
    }

    public function applyLeave(int $staffId, ApplyLeaveData $data): Leave
    {
        $start = Carbon::parse($data->start_date);
        $end = Carbon::parse($data->end_date);
        $totalDays = (int) $start->diffInWeekdays($end) + 1;

        $staff = Staff::findOrFail($staffId);

        return Leave::create([
            'school_id' => $staff->school_id,
            'staff_id' => $staffId,
            'leave_type_id' => $data->leave_type_id,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'total_days' => $totalDays,
            'reason' => $data->reason,
            'status' => 'pending',
        ]);
    }

    public function approveLeave(int $leaveId, ApproveLeaveData $data, int $approvedBy): Leave
    {
        $leave = Leave::findOrFail($leaveId);

        $leave->update([
            'status' => $data->status,
            'approved_by' => $approvedBy,
            'comment' => $data->comment,
        ]);

        return $leave;
    }

    public function calculateLeaveBalance(int $staffId, int $leaveTypeId): int
    {
        $leaveType = LeaveType::findOrFail($leaveTypeId);

        $usedDays = Leave::where('staff_id', $staffId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('total_days');

        return max(0, $leaveType->days_per_year - (int) $usedDays);
    }

    /** @return Collection<int, Payroll> */
    public function generatePayroll(int $schoolId, GeneratePayrollData $data): Collection
    {
        $generated = collect();

        $staffQuery = Staff::where('school_id', $schoolId)->with('user');

        if ($data->include_all_staff) {
            $staffQuery->active();
        }

        DB::transaction(function () use ($staffQuery, $data, $schoolId, &$generated) {
            foreach ($staffQuery->get() as $staff) {
                $existing = Payroll::where('school_id', $schoolId)
                    ->where('staff_id', $staff->id)
                    ->where('month', $data->month)
                    ->where('year', $data->year)
                    ->first();

                if ($existing) {
                    $generated->push($existing);

                    continue;
                }

                $gross = (float) $staff->basic_salary;
                $napsaEmployee = round($gross * 0.05, 2);
                $napsaEmployer = round($gross * 0.05, 2);
                $paye = $this->computePaye($gross);
                $netPay = round($gross - $napsaEmployee - $paye, 2);

                $payroll = Payroll::create([
                    'school_id' => $schoolId,
                    'staff_id' => $staff->id,
                    'month' => $data->month,
                    'year' => $data->year,
                    'basic_salary' => $gross,
                    'allowances' => 0,
                    'deductions' => 0,
                    'napsa_employee' => $napsaEmployee,
                    'napsa_employer' => $napsaEmployer,
                    'paye' => $paye,
                    'net_pay' => $netPay,
                ]);

                $generated->push($payroll);
            }
        });

        return $generated;
    }

    public function approvePayroll(int $payrollId, int $approvedBy): Payroll
    {
        $payroll = Payroll::findOrFail($payrollId);
        $payroll->update(['approved_by' => $approvedBy, 'paid_at' => now()]);

        return $payroll;
    }

    public function getStaffDirectory(int $schoolId): Collection
    {
        return Staff::where('school_id', $schoolId)
            ->with('user:id,name,email,profile_photo_path')
            ->orderBy('position')
            ->get();
    }

    /**
     * Zambia PAYE monthly tax bands (ZMW).
     * 0 – 4,800:       0%
     * 4,800 – 9,200:   25%
     * 9,200 – 14,400:  30%
     * > 14,400:        37.5%
     */
    private function computePaye(float $gross): float
    {
        $paye = 0.0;

        if ($gross > 14_400) {
            $paye += ($gross - 14_400) * 0.375;
            $gross = 14_400;
        }

        if ($gross > 9_200) {
            $paye += ($gross - 9_200) * 0.30;
            $gross = 9_200;
        }

        if ($gross > 4_800) {
            $paye += ($gross - 4_800) * 0.25;
        }

        return round($paye, 2);
    }
}
