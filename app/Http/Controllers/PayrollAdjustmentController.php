<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Services\HRService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollAdjustmentController extends Controller
{
    public function __construct(private readonly HRService $hrService) {}

    public function store(Request $request, Payroll $payroll): RedirectResponse
    {
        abort_if($payroll->school_id !== app('current_school')?->id, 403);
        abort_if(! $payroll->isPending(), 403, 'Cannot modify a paid payroll.');

        $this->authorize('payroll.generate');

        $data = $request->validate([
            'type'        => ['required', 'in:bonus,deduction'],
            'description' => ['required', 'string', 'max:200'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->hrService->addAdjustment($payroll, $data['type'], $data['description'], (float) $data['amount']);

        return back()->with('success', 'Adjustment added.');
    }

    public function destroy(PayrollAdjustment $adjustment): RedirectResponse
    {
        $payroll = $adjustment->payroll;
        abort_if($payroll->school_id !== app('current_school')?->id, 403);
        abort_if(! $payroll->isPending(), 403, 'Cannot modify a paid payroll.');

        $this->authorize('payroll.generate');

        $this->hrService->removeAdjustment($adjustment);

        return back()->with('success', 'Adjustment removed.');
    }
}
