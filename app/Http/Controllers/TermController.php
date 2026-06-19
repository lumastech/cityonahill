<?php

namespace App\Http\Controllers;

use App\Data\CreateTermData;
use App\Exceptions\ConflictException;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\CalendarService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TermController extends Controller
{
    public function __construct(private readonly CalendarService $calendarService) {}

    public function index(): Response
    {
        $school = app('current_school');

        $academicYears = AcademicYear::where('school_id', $school->id)
            ->with(['terms' => fn ($q) => $q->with('holidays')->orderBy('number')])
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Calendar/Index', [
            'academicYears' => $academicYears,
        ]);
    }

    public function store(CreateTermData $data): RedirectResponse
    {
        $school = app('current_school');

        try {
            $this->calendarService->createTerm($school->id, $data);
        } catch (ConflictException $e) {
            return back()->withErrors(['conflict' => $e->getMessage()]);
        }

        return redirect()->route('terms.index')
            ->with('success', 'Term created successfully.');
    }

    public function update(CreateTermData $data, Term $term): RedirectResponse
    {
        abort_if($term->school_id !== app('current_school')?->id, 403);

        $term->update($data->toArray());

        return redirect()->route('terms.index')
            ->with('success', 'Term updated successfully.');
    }

    public function destroy(Term $term): RedirectResponse
    {
        abort_if($term->school_id !== app('current_school')?->id, 403);

        $term->delete();

        return redirect()->route('terms.index')
            ->with('success', 'Term deleted.');
    }
}
