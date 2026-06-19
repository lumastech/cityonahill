<?php

namespace App\Services;

use App\Data\AdmitPupilData;
use App\Data\StoreGuardianData;
use App\Data\TransferPupilData;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Pupil;
use App\Models\PupilTransfer;
use App\Models\Stream;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PupilService
{
    public function admit(int $schoolId, AdmitPupilData $data): Pupil
    {
        return DB::transaction(function () use ($schoolId, $data) {
            $year = Carbon::parse($data->date_of_admission)->year;

            $admissionNo = Pupil::generateAdmissionNo($schoolId, $year);

            return Pupil::create([
                'school_id' => $schoolId,
                'admission_no' => $admissionNo,
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'other_name' => $data->other_name,
                'sex' => $data->sex,
                'dob' => $data->dob,
                'nationality' => $data->nationality,
                'religion' => $data->religion,
                'tribe' => $data->tribe,
                'disability' => $data->disability,
                'disability_details' => $data->disability_details,
                'blood_group' => $data->blood_group,
                'previous_school' => $data->previous_school,
                'date_of_admission' => $data->date_of_admission,
                'grade_id' => $data->grade_id,
                'stream_id' => $data->stream_id,
                'academic_year_id' => $data->academic_year_id,
                'status' => 'active',
            ]);
        });
    }

    public function addGuardian(int $pupilId, StoreGuardianData $data): Guardian
    {
        return DB::transaction(function () use ($pupilId, $data) {
            $pupil = Pupil::findOrFail($pupilId);

            $guardian = Guardian::firstOrCreate(
                ['phone' => $data->phone, 'school_id' => $pupil->school_id],
                [
                    'school_id' => $pupil->school_id,
                    'first_name' => $data->first_name,
                    'last_name' => $data->last_name,
                    'relationship' => $data->relationship,
                    'phone2' => $data->phone2,
                    'email' => $data->email,
                    'nrc' => $data->nrc,
                    'occupation' => $data->occupation,
                    'employer' => $data->employer,
                    'address' => $data->address,
                ]
            );

            if ($data->is_primary) {
                $pupil->guardians()->updateExistingPivot(
                    $pupil->guardians()->pluck('guardians.id')->toArray(),
                    ['is_primary' => 0]
                );
            }

            $pupil->guardians()->syncWithoutDetaching([
                $guardian->id => [
                    'is_primary' => $data->is_primary,
                    'is_emergency' => $data->is_emergency,
                    'can_pickup' => $data->can_pickup,
                ],
            ]);

            return $guardian;
        });
    }

    public function transfer(int $pupilId, TransferPupilData $data, int $approvedBy): Pupil
    {
        return DB::transaction(function () use ($pupilId, $data, $approvedBy) {
            $pupil = Pupil::findOrFail($pupilId);

            if ($data->type === 'external') {
                PupilTransfer::create([
                    'school_id' => $pupil->school_id,
                    'pupil_id' => $pupil->id,
                    'from_school' => $pupil->school->name,
                    'to_school' => $data->to_school,
                    'transfer_date' => $data->transfer_date,
                    'reason' => $data->reason,
                    'approved_by' => $approvedBy,
                ]);

                $pupil->update([
                    'status' => 'transferred',
                    'transfer_school' => $data->to_school,
                    'transfer_date' => $data->transfer_date,
                ]);
            } else {
                $pupil->update(['stream_id' => $data->stream_id]);
            }

            return $pupil->fresh();
        });
    }

    public function promoteGrade(int $pupilId, int $newGradeId, ?int $newStreamId): Pupil
    {
        $pupil = Pupil::findOrFail($pupilId);

        $currentYear = AcademicYear::where('school_id', $pupil->school_id)
            ->where('is_current', 1)
            ->value('id');

        $pupil->update([
            'grade_id' => $newGradeId,
            'stream_id' => $newStreamId,
            'academic_year_id' => $currentYear ?? $pupil->academic_year_id,
        ]);

        return $pupil->fresh();
    }

    public function bulkPromote(int $streamId, int $toGradeId, ?int $toStreamId): int
    {
        $stream = Stream::findOrFail($streamId);

        $pupils = Pupil::where('stream_id', $streamId)
            ->where('school_id', $stream->school_id)
            ->where('status', 'active')
            ->get();

        $currentYearId = AcademicYear::where('school_id', $stream->school_id)
            ->where('is_current', 1)
            ->value('id');

        foreach ($pupils as $pupil) {
            $pupil->update([
                'grade_id' => $toGradeId,
                'stream_id' => $toStreamId,
                'academic_year_id' => $currentYearId ?? $pupil->academic_year_id,
            ]);
        }

        return $pupils->count();
    }

    public function withdraw(int $pupilId, string $reason): Pupil
    {
        $pupil = Pupil::findOrFail($pupilId);

        $pupil->update(['status' => 'withdrawn']);

        AuditLog::create([
            'school_id' => $pupil->school_id,
            'user_id' => Auth::id(),
            'action' => 'withdrawn',
            'auditable_type' => Pupil::class,
            'auditable_id' => $pupil->id,
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'withdrawn', 'reason' => $reason],
            'ip_address' => request()->ip(),
        ]);

        return $pupil->fresh();
    }

    public function findByAdmissionNo(int $schoolId, string $admissionNo): ?Pupil
    {
        return Pupil::where('school_id', $schoolId)
            ->where('admission_no', $admissionNo)
            ->first();
    }

    public function getSchoolStatistics(int $schoolId): array
    {
        $base = Pupil::where('school_id', $schoolId);

        $byGrade = Pupil::where('school_id', $schoolId)
            ->where('status', 'active')
            ->select('grade_id', DB::raw('count(*) as count'))
            ->with('grade:id,name,grade_number')
            ->groupBy('grade_id')
            ->get()
            ->map(fn ($row) => [
                'grade' => $row->grade?->name,
                'count' => $row->count,
            ]);

        $byGender = Pupil::where('school_id', $schoolId)
            ->where('status', 'active')
            ->select('sex', DB::raw('count(*) as count'))
            ->groupBy('sex')
            ->pluck('count', 'sex');

        $byStatus = Pupil::where('school_id', $schoolId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total_pupils' => (clone $base)->count(),
            'by_grade' => $byGrade,
            'by_gender' => [
                'male' => $byGender['male'] ?? 0,
                'female' => $byGender['female'] ?? 0,
            ],
            'by_status' => [
                'active' => $byStatus['active'] ?? 0,
                'transferred' => $byStatus['transferred'] ?? 0,
                'withdrawn' => $byStatus['withdrawn'] ?? 0,
            ],
        ];
    }
}
