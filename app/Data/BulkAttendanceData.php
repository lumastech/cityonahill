<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BulkAttendanceData extends Data
{
    /**
     * @param  array<array{pupil_id: int, status: string, remarks: ?string}>  $records
     */
    public function __construct(
        public readonly int $session_id,
        public readonly array $records,
    ) {}

    public static function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:attendance_sessions,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.pupil_id' => ['required', 'integer', 'exists:pupils,id'],
            'records.*.status' => ['required', 'string', 'in:present,absent,late,excused,sick'],
            'records.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
