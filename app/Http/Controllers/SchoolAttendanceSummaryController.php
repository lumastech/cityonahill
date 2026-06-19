<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolAttendanceSummaryController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');

        $date = $request->input('date', today()->toDateString());

        return Inertia::render('Attendance/SchoolSummary', [
            'summary' => $this->attendanceService->getSchoolDailySummary($school->id, $date),
            'date' => $date,
        ]);
    }
}
