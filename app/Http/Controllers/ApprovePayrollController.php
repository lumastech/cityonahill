<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\HRService;
use Illuminate\Http\RedirectResponse;

class ApprovePayrollController extends Controller
{
    public function __construct(private readonly HRService $hrService) {}

    public function __invoke(Payroll $payroll): RedirectResponse
    {
        abort_if($payroll->school_id !== app('current_school')?->id, 403);

        $this->authorize('payroll.approve');

        $this->hrService->approvePayroll($payroll->id, auth()->id());

        return back()->with('success', 'Payroll approved and marked as paid.');
    }
}
