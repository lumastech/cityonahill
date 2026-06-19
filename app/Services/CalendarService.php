<?php

namespace App\Services;

use App\Data\CreateAcademicYearData;
use App\Data\CreateHolidayData;
use App\Data\CreateTermData;
use App\Exceptions\ConflictException;
use App\Models\AcademicYear;
use App\Models\SchoolHoliday;
use App\Models\Term;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarService
{
    public function createAcademicYear(int $schoolId, CreateAcademicYearData $data): AcademicYear
    {
        return DB::transaction(function () use ($schoolId, $data) {
            if ($data->is_current) {
                AcademicYear::where('school_id', $schoolId)->update(['is_current' => 0]);
            }

            return AcademicYear::create([
                'school_id' => $schoolId,
                'name' => $data->name,
                'start_date' => $data->start_date,
                'end_date' => $data->end_date,
                'is_current' => $data->is_current,
            ]);
        });
    }

    public function createTerm(int $schoolId, CreateTermData $data): Term
    {
        return DB::transaction(function () use ($schoolId, $data) {
            $termCount = Term::where('school_id', $schoolId)
                ->where('academic_year_id', $data->academic_year_id)
                ->count();

            if ($termCount >= 3) {
                throw new ConflictException('An academic year cannot have more than 3 terms.');
            }

            $isCurrent = Carbon::today()->between($data->start_date, $data->end_date);

            if ($isCurrent) {
                Term::where('school_id', $schoolId)->update(['is_current' => 0]);
            }

            return Term::create([
                'school_id' => $schoolId,
                'academic_year_id' => $data->academic_year_id,
                'name' => $data->name,
                'number' => $data->number,
                'start_date' => $data->start_date,
                'end_date' => $data->end_date,
                'is_current' => $isCurrent,
                'ca_deadline' => $data->ca_deadline,
                'exam_start' => $data->exam_start,
                'exam_end' => $data->exam_end,
            ]);
        });
    }

    public function setCurrentTerm(int $schoolId, Term $term): Term
    {
        return DB::transaction(function () use ($schoolId, $term) {
            Term::where('school_id', $schoolId)->update(['is_current' => 0]);
            $term->update(['is_current' => 1]);

            return $term->fresh();
        });
    }

    public function addHoliday(int $schoolId, CreateHolidayData $data): SchoolHoliday
    {
        return SchoolHoliday::create([
            'school_id' => $schoolId,
            'term_id' => $data->term_id,
            'name' => $data->name,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'type' => $data->type,
        ]);
    }

    public function getCurrentAcademicContext(int $schoolId): array
    {
        $year = AcademicYear::where('school_id', $schoolId)
            ->where('is_current', 1)
            ->with(['terms' => fn ($q) => $q->orderBy('number')])
            ->first();

        $term = Term::where('school_id', $schoolId)
            ->where('is_current', 1)
            ->with('holidays')
            ->first();

        return [
            'academic_year' => $year,
            'current_term' => $term,
            'terms' => $year?->terms ?? collect(),
        ];
    }
}
