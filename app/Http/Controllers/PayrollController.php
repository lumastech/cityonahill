<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $school = app('current_school');
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $payrolls = Payroll::where('school_id', $school->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('staff.user:id,name')
            ->get();

        return Inertia::render('HR/Payroll/Index', [
            'payrolls' => $payrolls,
            'filters'  => compact('month', 'year'),
        ]);
    }

    public function show(Payroll $payroll): Response
    {
        abort_if($payroll->school_id !== app('current_school')?->id, 403);

        $payroll->load([
            'staff.user:id,name,email',
            'adjustments' => fn ($q) => $q->orderBy('type')->orderBy('created_at'),
            'approvedBy:id,name',
        ]);

        return Inertia::render('HR/Payroll/Show', [
            'payroll'  => $payroll,
            'can_edit' => $payroll->isPending() && auth()->user()->can('payroll.generate'),
        ]);
    }
}
