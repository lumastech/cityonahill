<?php

namespace App\Http\Controllers;

use App\Data\AdmitPupilData;
use App\Data\UpdatePupilData;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Pupil;
use App\Models\Stream;
use App\Services\PupilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PupilController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $pupils = Pupil::where('school_id', $school->id)
            ->with(['grade:id,name', 'stream:id,name'])
            ->when($request->grade_id, fn ($q) => $q->where('grade_id', $request->grade_id))
            ->when($request->stream_id, fn ($q) => $q->where('stream_id', $request->stream_id))
            ->when($request->sex, fn ($q) => $q->where('sex', $request->sex))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('admission_no', 'like', "%{$request->search}%");
            }))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Pupils/Index', [
            'pupils' => $pupils,
            'filters' => $request->only(['grade_id', 'stream_id', 'sex', 'status', 'search']),
            'grades' => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name']),
            'stats' => Inertia::defer(fn () => $this->pupilService->getSchoolStatistics($school->id)),
        ]);
    }

    public function create(): Response
    {
        $school = app('current_school');

        return Inertia::render('Pupils/Create', [
            'grades' => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name', 'grade_number']),
            'streams' => Stream::where('school_id', $school->id)->get(['id', 'name', 'grade_id']),
            'academicYear' => AcademicYear::where('school_id', $school->id)->where('is_current', 1)->first(),
        ]);
    }

    public function store(AdmitPupilData $data): RedirectResponse
    {
        $this->authorize('pupil.create');

        $school = app('current_school');

        $pupil = $this->pupilService->admit($school->id, $data);

        return redirect()->route('pupils.show', $pupil)
            ->with('success', "Pupil {$pupil->full_name} admitted with number {$pupil->admission_no}.");
    }

    public function show(Pupil $pupil): Response
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $pupil->load([
            'grade',
            'stream',
            'academicYear',
            'guardians',
            'transfers',
        ]);

        return Inertia::render('Pupils/Show', [
            'pupil' => $pupil,
            'attendanceSummary' => [],
            'termResults' => [],
        ]);
    }

    public function edit(Pupil $pupil): Response
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $school = app('current_school');

        return Inertia::render('Pupils/Edit', [
            'pupil' => $pupil,
            'grades' => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name', 'grade_number']),
            'streams' => Stream::where('school_id', $school->id)->get(['id', 'name', 'grade_id']),
        ]);
    }

    public function update(UpdatePupilData $data, Pupil $pupil): RedirectResponse
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $pupil->update(array_filter($data->toArray(), fn ($v) => $v !== null));

        return redirect()->route('pupils.show', $pupil)
            ->with('success', 'Pupil record updated.');
    }

    public function destroy(Pupil $pupil): RedirectResponse
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        if ($pupil->termResults()->exists()) {
            $this->pupilService->withdraw($pupil->id, 'Record deletion requested — withdrawn instead.');

            return redirect()->route('pupils.index')
                ->with('info', 'Pupil has academic records and cannot be deleted. Status set to withdrawn.');
        }

        $pupil->delete();

        return redirect()->route('pupils.index')
            ->with('success', 'Pupil record deleted.');
    }
}
