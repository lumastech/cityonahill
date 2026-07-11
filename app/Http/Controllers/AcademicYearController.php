<?php

namespace App\Http\Controllers;

use App\Data\CreateAcademicYearData;
use App\Models\AcademicYear;
use App\Services\CalendarService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function __construct(private readonly CalendarService $calendarService) {}

    public function index(): Response
    {
        $school = app('current_school');

        $years = AcademicYear::where('school_id', $school->id)
            ->withCount('terms')
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Calendar/AcademicYears/Index', [
            'academicYears' => $years,
        ]);
    }

    public function store(CreateAcademicYearData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->calendarService->createAcademicYear($school->id, $data);

        return redirect()->route('academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    public function update(CreateAcademicYearData $data, AcademicYear $academicYear): RedirectResponse
    {
        abort_if($academicYear->school_id !== app('current_school')?->id, 403);

        $this->calendarService->updateAcademicYear($academicYear, $data);

        return redirect()->route('academic-years.index')
            ->with('success', 'Academic year updated successfully.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        abort_if($academicYear->school_id !== app('current_school')?->id, 403);

        $academicYear->delete();

        return redirect()->route('academic-years.index')
            ->with('success', 'Academic year deleted.');
    }
}
