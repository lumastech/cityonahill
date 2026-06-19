<?php

namespace App\Http\Controllers;

use App\Data\GeneratePayrollData;
use App\Services\HRService;
use Illuminate\Http\RedirectResponse;

class GeneratePayrollController extends Controller
{
    public function __construct(private readonly HRService $hrService) {}

    public function __invoke(GeneratePayrollData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->authorize('payroll.generate');

        $payrolls = $this->hrService->generatePayroll($school->id, $data);

        return back()->with('success', "Payroll generated for {$payrolls->count()} staff members.");
    }
}
