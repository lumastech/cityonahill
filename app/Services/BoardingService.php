<?php

namespace App\Services;

use App\Data\AllocateBedData;
use App\Data\CreateDormitoryData;
use App\Data\VacateBedData;
use App\Enums\SexEnum;
use App\Models\Bed;
use App\Models\BoardingAllocation;
use App\Models\Dormitory;
use App\Models\FeeInvoice;
use App\Models\Pupil;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BoardingService
{
    public function createDormitory(int $schoolId, CreateDormitoryData $data): Dormitory
    {
        return Dormitory::create([
            'school_id' => $schoolId,
            'name' => $data->name,
            'gender' => $data->gender,
            'capacity' => $data->capacity,
            'description' => $data->description,
        ]);
    }

    public function allocateBed(int $schoolId, AllocateBedData $data): BoardingAllocation
    {
        $pupil = Pupil::findOrFail($data->pupil_id);
        $bed = Bed::with('dormitory')->findOrFail($data->bed_id);

        // Gender check: pupil sex must match dormitory gender
        $pupilGender = $pupil->sex instanceof SexEnum
            ? $pupil->sex->value
            : (string) $pupil->sex;

        if ($pupilGender !== $bed->dormitory->gender) {
            throw ValidationException::withMessages([
                'pupil_id' => "This pupil is {$pupilGender} but the dormitory is for {$bed->dormitory->gender} pupils.",
            ]);
        }

        if ($bed->status !== 'available') {
            throw ValidationException::withMessages([
                'bed_id' => 'This bed is no longer available.',
            ]);
        }

        $alreadyAllocated = BoardingAllocation::where('school_id', $schoolId)
            ->where('pupil_id', $data->pupil_id)
            ->where('term_id', $data->term_id)
            ->active()
            ->exists();

        if ($alreadyAllocated) {
            throw ValidationException::withMessages([
                'pupil_id' => "{$pupil->first_name} {$pupil->last_name} already has a bed allocated for this term.",
            ]);
        }

        $allocation = BoardingAllocation::create([
            'school_id' => $schoolId,
            'pupil_id' => $data->pupil_id,
            'bed_id' => $data->bed_id,
            'term_id' => $data->term_id,
            'allocated_date' => now()->toDateString(),
            'fee_amount' => $data->fee_amount,
        ]);

        $bed->update(['status' => 'occupied']);

        if ($data->fee_amount > 0) {
            $term = Term::find($data->term_id);
            FeeInvoice::create([
                'school_id'        => $schoolId,
                'pupil_id'         => $data->pupil_id,
                'fee_structure_id' => null,
                'term_id'          => $data->term_id,
                'academic_year_id' => $term?->academic_year_id,
                'notes'            => "Boarding fee — {$bed->dormitory->name}, Bed {$bed->bed_number}",
                'amount'           => $data->fee_amount,
                'discount'         => 0,
                'balance_due'      => $data->fee_amount,
                'status'           => 'unpaid',
            ]);
        }

        return $allocation;
    }

    public function vacateBed(int $allocationId, VacateBedData $data): BoardingAllocation
    {
        $allocation = BoardingAllocation::with('bed')->findOrFail($allocationId);

        $allocation->update([
            'status' => 'vacated',
            'vacated_date' => now()->toDateString(),
        ]);

        $allocation->bed->update(['status' => 'available']);

        return $allocation;
    }

    public function getDormitoryOccupancy(int $schoolId): Collection
    {
        return Dormitory::where('school_id', $schoolId)
            ->withCount([
                'beds as total_beds',
                'beds as occupied_count' => fn ($q) => $q->where('status', 'occupied'),
                'beds as available_count' => fn ($q) => $q->where('status', 'available'),
            ])
            ->orderBy('gender')
            ->orderBy('name')
            ->get();
    }

    public function getTermRoster(int $schoolId, int $termId): Collection
    {
        return BoardingAllocation::where('school_id', $schoolId)
            ->where('term_id', $termId)
            ->active()
            ->with([
                'pupil:id,first_name,last_name,admission_no,stream_id',
                'pupil.stream:id,name,grade_id',
                'pupil.stream.grade:id,name',
                'pupil.guardians:id,first_name,last_name,phone',
                'bed:id,bed_number,dormitory_id',
                'bed.dormitory:id,name,gender',
            ])
            ->orderBy('bed_id')
            ->get();
    }
}
