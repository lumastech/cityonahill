<?php

namespace App\Http\Controllers;

use App\Data\StoreStreamData;
use App\Models\Stream;
use App\Services\ClassStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'streams' => $streams,
            'filterGradeId' => $request->integer('grade_id') ?: null,
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

        return Inertia::render('Streams/Edit', [
            'stream' => $stream->load(['grade', 'classTeacher']),
        ]);
    }

    public function store(StoreStreamData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->service->createStream($school->id, $data);

        return redirect()->route('streams.index')
            ->with('success', 'Class created successfully.');
    }

    public function update(StoreStreamData $data, Stream $stream): RedirectResponse
    {
        $this->authorizeSchool($stream);

        $stream->update([
            'grade_id' => $data->grade_id,
            'name' => $data->name,
            'class_teacher_id' => $data->class_teacher_id,
            'capacity' => $data->capacity,
            'academic_year_id' => $data->academic_year_id,
        ]);

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
