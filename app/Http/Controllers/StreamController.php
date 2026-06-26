<?php

namespace App\Http\Controllers;

use App\Data\StoreStreamData;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\User;
use App\Services\ClassStructureService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StreamController extends Controller
{
    public function __construct(private readonly ClassStructureService $service) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $streams = Stream::with(['grade', 'classTeacher'])
            ->where('school_id', $school->id)
            ->when($request->integer('grade_id'), fn ($q, $gradeId) => $q->forGrade($gradeId))
            ->withCount('pupils')
            ->orderBy('name')
            ->get();

        return Inertia::render('Streams/Index', [
            'streams'        => $streams,
            'filterGradeId'  => $request->integer('grade_id') ?: null,
            'grades'         => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name', 'grade_number']),
            'teachers'       => User::whereHas('staff', fn ($q) => $q->where('school_id', $school->id))->orderBy('name')->get(['id', 'name']),
            'academic_years' => AcademicYear::where('school_id', $school->id)->orderByDesc('start_year')->get(['id', 'name']),
        ]);
    }

    public function show(Stream $stream): Response
    {
        $this->authorizeSchool($stream);

        $timetable = $this->service->getStreamTimetable($stream->id);

        return Inertia::render('Streams/Show', [
            'stream' => $stream->load(['grade', 'classTeacher']),
            'timetable' => $timetable,
            'pupils' => [],  // stub — filled in Module 3
        ]);
    }

    public function edit(Stream $stream): Response
    {
        $this->authorizeSchool($stream);

        $school = app('current_school');

        return Inertia::render('Streams/Edit', [
            'stream'         => $stream->load(['grade', 'classTeacher']),
            'grades'         => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name', 'grade_number']),
            'teachers'       => User::whereHas('staff', fn ($q) => $q->where('school_id', $school->id))->orderBy('name')->get(['id', 'name']),
            'academic_years' => AcademicYear::where('school_id', $school->id)->orderByDesc('start_year')->get(['id', 'name']),
        ]);
    }

    public function store(StoreStreamData $data): RedirectResponse
    {
        $school = app('current_school');

        try {
            $this->service->createStream($school->id, $data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => "A class named \"{$data->name}\" already exists in the selected grade.",
            ]);
        }

        return redirect()->route('streams.index')
            ->with('success', 'Class created successfully.');
    }

    public function update(StoreStreamData $data, Stream $stream): RedirectResponse
    {
        $this->authorizeSchool($stream);

        try {
            $stream->update([
                'grade_id' => $data->grade_id,
                'name' => $data->name,
                'class_teacher_id' => $data->class_teacher_id,
                'capacity' => $data->capacity,
                'academic_year_id' => $data->academic_year_id,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => "A class named \"{$data->name}\" already exists in the selected grade.",
            ]);
        }

        return redirect()->route('streams.index')
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(Stream $stream): RedirectResponse
    {
        $this->authorizeSchool($stream);

        $stream->delete();

        return redirect()->route('streams.index')
            ->with('success', 'Class deleted.');
    }

    private function authorizeSchool(Stream $stream): void
    {
        $school = app('current_school');

        abort_if($stream->school_id !== $school?->id, 403);
    }
}
