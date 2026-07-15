<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Stream;
use App\Services\PupilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PupilImportController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function create(): Response
    {
        $this->authorize('pupil.create');

        $school = app('current_school');

        return Inertia::render('Pupils/Import', [
            'grades'       => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name', 'grade_number']),
            'streams'      => Stream::where('school_id', $school->id)->get(['id', 'name', 'grade_id']),
            'academicYear' => AcademicYear::where('school_id', $school->id)->where('is_current', 1)->first(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('pupil.create');

        $school = app('current_school');

        $validated = $request->validate([
            'grade_id'             => ['required', 'integer', Rule::exists('grades', 'id')->where('school_id', $school->id)],
            'stream_id'            => ['nullable', 'integer', Rule::exists('streams', 'id')->where('school_id', $school->id)],
            'academic_year_id'     => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'date_of_admission'    => ['required', 'date'],
            'rows'                 => ['required', 'array', 'min:1'],
            'rows.*.name'          => ['required', 'string', 'max:100'],
            'rows.*.sex'           => ['required', 'string', 'max:10'],
            'rows.*.dob'           => ['required', 'string', 'max:20'],
            'rows.*.guardian_name' => ['nullable', 'string', 'max:100'],
            'rows.*.guardian_phone' => ['nullable', 'string', 'max:25'],
        ]);

        $result = $this->pupilService->bulkImport($school->id, $validated);

        $summary = "{$result['created']} " . str('pupil')->plural($result['created']) . ' imported.';

        if (count($result['skipped']) > 0) {
            $summary .= ' ' . count($result['skipped']) . ' skipped (already on the register).';
        }

        if (count($result['errors']) > 0) {
            return redirect()->route('pupils.import')
                ->with('info', $summary . ' ' . count($result['errors']) . ' row(s) had problems: ' . implode(' ', $result['errors']));
        }

        return redirect()->route('pupils.index')->with('success', $summary);
    }
}
