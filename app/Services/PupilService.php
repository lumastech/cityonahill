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

            $pupil = Pupil::create([
                'school_id'        => $schoolId,
                'admission_no'     => Pupil::generateAdmissionNo($schoolId, $year),
                'first_name'       => $data->first_name,
                'last_name'        => $data->last_name,
                'other_name'       => $data->other_name,
                'sex'              => $data->sex,
                'dob'              => $data->dob,
                'nationality'      => $data->nationality,
                'religion'         => $data->religion,
                'tribe'            => $data->tribe,
                'disability'       => $data->disability,
                'disability_details' => $data->disability_details,
                'blood_group'      => $data->blood_group,
                'previous_school'  => $data->previous_school,
                'date_of_admission' => $data->date_of_admission,
                'grade_id'         => $data->grade_id,
                'stream_id'        => $data->stream_id,
                'academic_year_id' => $data->academic_year_id,
                'status'           => 'active',
            ]);

            if ($data->guardian_phone && $data->guardian_first_name && $data->guardian_last_name) {
                $guardian = Guardian::firstOrCreate(
                    ['phone' => $data->guardian_phone, 'school_id' => $schoolId],
                    [
                        'school_id'    => $schoolId,
                        'first_name'   => $data->guardian_first_name,
                        'last_name'    => $data->guardian_last_name,
                        'relationship' => $data->guardian_relationship ?? 'guardian',
                        'email'        => $data->guardian_email,
                    ]
                );

                $pupil->guardians()->syncWithoutDetaching([
                    $guardian->id => [
                        'is_primary'   => $data->is_primary,
                        'is_emergency' => $data->is_emergency,
                        'can_pickup'   => $data->can_pickup,
                    ],
                ]);
            }

            return $pupil;
        });
    }

    /**
     * Bulk-admit a batch of pupils into a single grade / stream placement.
     *
     * Each row is a raw associative array with keys: name, sex, dob and the
     * optional guardian_name / guardian_phone. Names are split into first /
     * last, sex is normalised to male|female and the date of birth is parsed
     * from the day-first formats commonly used on paper registers. Rows that
     * cannot be parsed, or that duplicate an existing pupil, are skipped and
     * reported rather than aborting the whole import.
     *
     * @param  array{grade_id:int, stream_id:?int, academic_year_id:int, date_of_admission:string, rows:array<int,array<string,mixed>>}  $data
     * @return array{created:int, skipped:array<int,string>, errors:array<int,string>}
     */
    public function bulkImport(int $schoolId, array $data): array
    {
        $created = 0;
        $skipped = [];
        $errors = [];

        // Spreadsheets often rewrite dates into US month-first order, so the
        // whole batch is inspected once to decide day-first vs month-first and
        // every row is then parsed the same way.
        $dateOrder = $this->detectDateOrder($data['rows']);

        DB::transaction(function () use ($schoolId, $data, $dateOrder, &$created, &$skipped, &$errors) {
            $year = Carbon::parse($data['date_of_admission'])->year;

            foreach ($data['rows'] as $index => $row) {
                $line = $index + 1;
                $rawName = trim((string) ($row['name'] ?? ''));

                [$firstName, $lastName] = $this->splitName($rawName);

                if ($firstName === '' || $lastName === '') {
                    $errors[] = "Row {$line}: \"{$rawName}\" — a first and last name are both required.";

                    continue;
                }

                $sex = $this->normaliseSex($row['sex'] ?? null);

                if ($sex === null) {
                    $errors[] = "Row {$line} ({$rawName}): sex must be M or F.";

                    continue;
                }

                $dob = $this->parseDob($row['dob'] ?? null, $dateOrder);

                if ($dob === null) {
                    $errors[] = "Row {$line} ({$rawName}): could not read date of birth \"" . ($row['dob'] ?? '') . '".';

                    continue;
                }

                $duplicate = Pupil::where('school_id', $schoolId)
                    ->where('first_name', $firstName)
                    ->where('last_name', $lastName)
                    ->whereDate('dob', $dob)
                    ->exists();

                if ($duplicate) {
                    $skipped[] = "{$firstName} {$lastName} — already on the register.";

                    continue;
                }

                $pupil = Pupil::create([
                    'school_id'         => $schoolId,
                    'admission_no'      => Pupil::generateAdmissionNo($schoolId, $year),
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'sex'               => $sex,
                    'dob'               => $dob,
                    'nationality'       => 'Zambian',
                    'disability'        => 'none',
                    'date_of_admission' => $data['date_of_admission'],
                    'grade_id'          => $data['grade_id'],
                    'stream_id'         => $data['stream_id'],
                    'academic_year_id'  => $data['academic_year_id'],
                    'status'            => 'active',
                ]);

                $this->attachImportedGuardian($pupil, $row);

                $created++;
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors'  => $errors,
        ];
    }

    private function attachImportedGuardian(Pupil $pupil, array $row): void
    {
        $phone = preg_replace('/\s+/', '', (string) ($row['guardian_phone'] ?? ''));
        [$guardianFirst, $guardianLast] = $this->splitName(trim((string) ($row['guardian_name'] ?? '')));

        if ($phone === '' || $guardianFirst === '') {
            return;
        }

        $guardian = Guardian::firstOrCreate(
            ['phone' => $phone, 'school_id' => $pupil->school_id],
            [
                'school_id'    => $pupil->school_id,
                'first_name'   => $guardianFirst,
                'last_name'    => $guardianLast !== '' ? $guardianLast : $guardianFirst,
                'relationship' => 'guardian',
            ]
        );

        $pupil->guardians()->syncWithoutDetaching([
            $guardian->id => [
                'is_primary'   => true,
                'is_emergency' => true,
                'can_pickup'   => true,
            ],
        ]);
    }

    /**
     * Split a full name into [first, last]. The first whitespace-delimited
     * token is the first name; everything after it is the last name.
     *
     * @return array{0:string, 1:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) === 0) {
            return ['', ''];
        }

        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $first = array_shift($parts);

        return [$first, implode(' ', $parts)];
    }

    private function normaliseSex(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'm', 'male'   => 'male',
            'f', 'female' => 'female',
            default       => null,
        };
    }

    /**
     * Inspect every date of birth in the batch and decide whether the file is
     * written day-first (10/05/21) or month-first (05/10/21). A part greater
     * than 12 can only be a day, so those rows cast the deciding votes; when
     * there is no evidence either way we default to day-first (Zambian norm).
     *
     * @param  array<int,array<string,mixed>>  $rows
     */
    private function detectDateOrder(array $rows): string
    {
        $dayFirst = 0;
        $monthFirst = 0;

        foreach ($rows as $row) {
            $value = trim((string) ($row['dob'] ?? ''));

            if (! preg_match('#^(\d{1,2})[/-](\d{1,2})[/-]\d{2,4}$#', $value, $m)) {
                continue;
            }

            $a = (int) $m[1];
            $b = (int) $m[2];

            if ($a > 12 && $b <= 12) {
                $dayFirst++;
            } elseif ($b > 12 && $a <= 12) {
                $monthFirst++;
            }
        }

        return $monthFirst > $dayFirst ? 'mdy' : 'dmy';
    }

    private function parseDob(mixed $value, string $order = 'dmy'): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = $order === 'mdy'
            ? ['m/d/y', 'm/d/Y', 'n/j/y', 'n/j/Y', 'm-d-y', 'm-d-Y', 'n-j-y', 'n-j-Y', 'Y-m-d']
            : ['d/m/y', 'd/m/Y', 'j/n/y', 'j/n/Y', 'd-m-y', 'd-m-Y', 'j-n-y', 'j-n-Y', 'Y-m-d'];

        foreach ($formats as $format) {
            // Carbon throws on genuinely unparseable input and silently rolls
            // over out-of-range parts (e.g. month 15), so we swallow the
            // exception and confirm the parse round-trips to the same string.
            try {
                $date = Carbon::createFromFormat($format, $value);
            } catch (\Exception) {
                continue;
            }

            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
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
                $stream = Stream::findOrFail($data->stream_id);
                $pupil->update([
                    'stream_id' => $stream->id,
                    'grade_id'  => $stream->grade_id,
                ]);
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
