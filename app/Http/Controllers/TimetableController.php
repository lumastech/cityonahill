<?php

namespace App\Http\Controllers;

use App\Data\StoreTimetableSlotData;
use App\Exceptions\ConflictException;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\ClassStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimetableController extends Controller
{
    public function __construct(private readonly ClassStructureService $service) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        if ($streamId = $request->integer('stream_id')) {
            $timetable = $this->service->getStreamTimetable($streamId);
            $viewMode = 'stream';
        } elseif ($teacherId = $request->integer('teacher_id')) {
            $timetable = $this->service->getTeacherTimetable($teacherId);
            $viewMode = 'teacher';
        } else {
            $timetable = collect();
            $viewMode = null;
        }

        return Inertia::render('Timetable/Index', [
            'timetable' => $timetable,
            'viewMode'  => $viewMode,
            'streamId'  => $request->integer('stream_id') ?: null,
            'teacherId' => $request->integer('teacher_id') ?: null,
            'streams'   => Stream::where('school_id', $school->id)
                ->with('grade:id,name')
                ->orderBy('grade_id')
                ->get(['id', 'name', 'grade_id']),
            'subjects'  => Subject::where('school_id', $school->id)->orderBy('name')->get(['id', 'name', 'code']),
            'teachers'  => User::whereHas('staff', fn ($q) => $q->where('school_id', $school->id))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * @throws ConflictException
     */
    public function store(StoreTimetableSlotData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->service->createTimetableSlot($school->id, $data);

        return redirect()->route('timetable.index', ['stream_id' => $data->stream_id])
            ->with('success', 'Timetable slot added.');
    }

    public function destroy(TimetableSlot $timetable): RedirectResponse
    {
        $school = app('current_school');

        abort_if($timetable->school_id !== $school?->id, 403);

        $streamId = $timetable->stream_id;

        $timetable->delete();

        return redirect()->route('timetable.index', ['stream_id' => $streamId])
            ->with('success', 'Timetable slot removed.');
    }
}
