<?php

namespace App\Http\Controllers;

use App\Data\CreateHolidayData;
use App\Models\SchoolHoliday;
use App\Services\CalendarService;
use Illuminate\Http\RedirectResponse;

class HolidayController extends Controller
{
    public function __construct(private readonly CalendarService $calendarService) {}

    public function store(CreateHolidayData $data): RedirectResponse
    {
        $school = app('current_school');

        $this->calendarService->addHoliday($school->id, $data);

        return back()->with('success', 'Holiday added successfully.');
    }

    public function destroy(SchoolHoliday $schoolHoliday): RedirectResponse
    {
        abort_if($schoolHoliday->school_id !== app('current_school')?->id, 403);

        $schoolHoliday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
