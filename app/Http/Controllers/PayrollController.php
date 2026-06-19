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
            'filters' => compact('month', 'year'),
        ]);
    }
}
