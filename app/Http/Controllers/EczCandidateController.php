<?php

namespace App\Http\Controllers;

use App\Data\AddSubjectEntryData;
use App\Data\RegisterCandidateData;
use App\Data\UpdateIndexNumberData;
use App\Models\EczCandidate;
use App\Models\Subject;
use App\Services\EczService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EczCandidateController extends Controller
{
    public function __construct(private readonly EczService $eczService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');
        $gradeLevel = $request->integer('grade_level') ?: 12;
        $examYear = $request->integer('exam_year') ?: now()->year;

        $candidates = $this->eczService->getCandidateList($school->id, $gradeLevel, $examYear);

        return Inertia::render('ECZ/Candidates/Index', [
            'candidates' => $candidates,
            'filters' => ['grade_level' => $gradeLevel, 'exam_year' => $examYear],
        ]);
    }

    public function store(RegisterCandidateData $data): RedirectResponse
    {
        $school = app('current_school');

        $candidate = $this->eczService->registerCandidate($school->id, $data);

        return redirect()->route('ecz-candidates.show', $candidate)
            ->with('success', 'Candidate registered.');
    }

    public function show(EczCandidate $eczCandidate): Response
    {
        abort_if($eczCandidate->school_id !== app('current_school')?->id, 403);

        $eczCandidate->load([
            'pupil:id,first_name,last_name,admission_no,sex',
            'subjectEntries.subject:id,name,code',
            'result',
        ]);

        $subjects = Subject::where('school_id', $eczCandidate->school_id)
            ->ecz()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('ECZ/Candidates/Show', [
            'candidate' => $eczCandidate,
            'subjects' => $subjects,
        ]);
    }

    public function update(Request $request, EczCandidate $eczCandidate): RedirectResponse
    {
        abort_if($eczCandidate->school_id !== app('current_school')?->id, 403);

        $action = $request->input('action');

        if ($action === 'add_subjects') {
            $data = AddSubjectEntryData::from($request->all());
            $enteredBy = $request->user()->id;
            $this->eczService->addSubjectEntries($eczCandidate->id, [$data->subject_id], $enteredBy);

            if ($data->predicted_grade) {
                $entry = $eczCandidate->subjectEntries()
                    ->where('subject_id', $data->subject_id)
                    ->first();
                if ($entry) {
                    $this->eczService->setPredictedGrade($entry->id, $data->predicted_grade);
                }
            }

            return back()->with('success', 'Subject entry added.');
        }

        $data = UpdateIndexNumberData::from($request->all());
        $this->eczService->updateIndexNumber($eczCandidate->id, $data);

        return back()->with('success', 'Index number updated.');
    }

    public function destroy(EczCandidate $eczCandidate): RedirectResponse
    {
        abort_if($eczCandidate->school_id !== app('current_school')?->id, 403);

        $eczCandidate->delete();

        return redirect()->route('ecz-candidates.index')
            ->with('success', 'Candidate removed.');
    }
}
