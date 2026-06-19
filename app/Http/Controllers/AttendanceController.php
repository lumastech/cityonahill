<?php

namespace App\Http\Controllers;

use App\Data\BulkAttendanceData;
use App\Data\OpenAttendanceSessionData;
use App\Exceptions\ConflictException;
use App\Models\AttendanceSession;
use App\Models\Stream;
use App\Models\Term;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $streams = Stream::where('school_id', $school->id)
            ->with('grade:id,name,grade_number')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_id', 'class_teacher_id']);

        $userStream = $streams->firstWhere('class_teacher_id', auth()->id());

        $selectedStreamId = $request->integer('stream_id') ?: $userStream?->id;
        $selectedDate = $request->input('date', today()->toDateString());

        $register = $selectedStreamId
            ? $this->attendanceService->getClassRegister($selectedStreamId, $selectedDate)
            : null;

        return Inertia::render('Attendance/ClassRegister', [
            'streams' => $streams,
            'selectedStreamId' => $selectedStreamId,
            'selectedDate' => $selectedDate,
            'register' => $register,
            'terms' => Term::where('school_id', $school->id)->orderBy('number')->get(['id', 'name', 'number', 'is_current']),
        ]);
    }

    public function store(OpenAttendanceSessionData $data): RedirectResponse
    {
        $school = app('current_school');

        try {
            $this->attendanceService->openSession($school->id, $data);
        } catch (ConflictException $e) {
            return back()->withErrors(['conflict' => $e->getMessage()]);
        }

        return redirect()->route('attendance.index', [
            'stream_id' => $data->stream_id,
            'date' => $data->date,
        ])->with('success', 'Attendance register opened.');
    }

    public function update(Request $request, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_if($attendanceSession->school_id !== app('current_school')?->id, 403);

        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.pupil_id' => ['required', 'integer', 'exists:pupils,id'],
            'records.*.status' => ['required', 'string', 'in:present,absent,late,excused,sick'],
            'records.*.remarks' => ['nullable', 'string', 'max:255'],
            'finalize' => ['boolean'],
        ]);

        $bulkData = new BulkAttendanceData(
            session_id: $attendanceSession->id,
            records: $validated['records'],
        );

        $this->attendanceService->recordAttendance($bulkData, auth()->id());

        if ($request->boolean('finalize')) {
            $this->attendanceService->finalizeSession($attendanceSession->id);
        }

        return back()->with('success', 'Attendance saved successfully.');
    }
}
