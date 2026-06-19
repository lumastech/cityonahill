<?php

namespace App\Http\Controllers;

use App\Models\Pupil;
use App\Models\Term;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceReportController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');

        $pupilId = $request->integer('pupil_id') ?: null;
        $termId = $request->integer('term_id') ?: null;

        $summary = ($pupilId && $termId)
            ? $this->attendanceService->getTermSummary($pupilId, $termId)
            : null;

        return Inertia::render('Attendance/Report', [
            'summary' => $summary,
            'pupil_id' => $pupilId,
            'term_id' => $termId,
            'pupils' => Inertia::defer(fn () => Pupil::where('school_id', $school->id)
                ->where('status', 'active')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no'])
            ),
            'terms' => Term::where('school_id', $school->id)->orderBy('number')->get(['id', 'name', 'number']),
        ]);
    }
}
