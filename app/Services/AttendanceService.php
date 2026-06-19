<?php

namespace App\Services;

use App\Data\BulkAttendanceData;
use App\Data\OpenAttendanceSessionData;
use App\Exceptions\ConflictException;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Pupil;
use App\Models\Stream;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function openSession(int $schoolId, OpenAttendanceSessionData $data): AttendanceSession
    {
        return DB::transaction(function () use ($schoolId, $data) {
            $exists = AttendanceSession::where('school_id', $schoolId)
                ->where('stream_id', $data->stream_id)
                ->whereDate('date', $data->date)
                ->where('session_type', $data->session_type)
                ->exists();

            if ($exists) {
                throw new ConflictException(
                    "Attendance register already opened for this stream on {$data->date} ({$data->session_type})."
                );
            }

            $session = AttendanceSession::create([
                'school_id' => $schoolId,
                'stream_id' => $data->stream_id,
                'term_id' => $data->term_id,
                'date' => $data->date,
                'session_type' => $data->session_type,
                'finalized' => 0,
            ]);

            $pupils = Pupil::where('stream_id', $data->stream_id)
                ->where('status', 'active')
                ->pluck('id');

            $now = now();

            AttendanceRecord::insert(
                $pupils->map(fn ($id) => [
                    'session_id' => $session->id,
                    'pupil_id' => $id,
                    'status' => 'absent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray()
            );

            return $session;
        });
    }

    public function recordAttendance(BulkAttendanceData $data, int $recordedBy): Collection
    {
        $now = now();

        $upsertData = collect($data->records)->map(fn ($record) => [
            'session_id' => $data->session_id,
            'pupil_id' => $record['pupil_id'],
            'status' => $record['status'],
            'remarks' => $record['remarks'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        AttendanceRecord::upsert(
            $upsertData,
            ['session_id', 'pupil_id'],
            ['status', 'remarks', 'updated_at']
        );

        $session = AttendanceSession::findOrFail($data->session_id);

        $totalActivePupils = Pupil::where('stream_id', $session->stream_id)
            ->where('status', 'active')
            ->count();

        if (count($data->records) >= $totalActivePupils) {
            $session->update(['recorded_by' => $recordedBy, 'finalized' => 1]);
        } else {
            $session->update(['recorded_by' => $recordedBy]);
        }

        return AttendanceRecord::where('session_id', $data->session_id)
            ->with('pupil:id,first_name,last_name,admission_no')
            ->get();
    }

    public function finalizeSession(int $sessionId): AttendanceSession
    {
        $session = AttendanceSession::findOrFail($sessionId);
        $session->update(['finalized' => 1]);

        return $session->fresh();
    }

    public function getClassRegister(int $streamId, string $date): array
    {
        $session = AttendanceSession::forStream($streamId)
            ->forDate($date)
            ->with([
                'records.pupil:id,first_name,last_name,admission_no',
                'recordedBy:id,name',
            ])
            ->first();

        if (! $session) {
            $pupils = Pupil::where('stream_id', $streamId)
                ->where('status', 'active')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no']);

            return [
                'session' => null,
                'records' => [],
                'pupils' => $pupils,
            ];
        }

        $records = $session->records->map(fn ($record) => [
            'record_id' => $record->id,
            'pupil_id' => $record->pupil_id,
            'pupil_name' => $record->pupil?->full_name,
            'admission_no' => $record->pupil?->admission_no,
            'status' => $record->status,
            'remarks' => $record->remarks,
        ])->sortBy('pupil_name')->values();

        return [
            'session' => $session,
            'records' => $records,
            'pupils' => collect(),
        ];
    }

    public function getTermSummary(int $pupilId, int $termId): array
    {
        $pupil = Pupil::findOrFail($pupilId);

        $total_days = AttendanceSession::where('term_id', $termId)
            ->where('stream_id', $pupil->stream_id)
            ->where('finalized', 1)
            ->count();

        $counts = AttendanceRecord::where('pupil_id', $pupilId)
            ->whereHas('attendanceSession', fn ($q) => $q->where('term_id', $termId)->where('finalized', 1))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $present = (int) ($counts['present'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $late = (int) ($counts['late'] ?? 0);
        $excused = (int) ($counts['excused'] ?? 0);
        $sick = (int) ($counts['sick'] ?? 0);

        $percentage = $total_days > 0
            ? round(($present + $late) / $total_days * 100, 1)
            : 0.0;

        $daily = AttendanceRecord::where('pupil_id', $pupilId)
            ->whereHas('attendanceSession', fn ($q) => $q->where('term_id', $termId))
            ->with('attendanceSession:id,date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->attendanceSession->date->toDateString(),
                'status' => $r->status,
            ])
            ->sortBy('date')
            ->values()
            ->toArray();

        return compact('total_days', 'present', 'absent', 'late', 'excused', 'sick', 'percentage', 'daily');
    }

    public function getSchoolDailySummary(int $schoolId, string $date): array
    {
        $sessions = AttendanceSession::where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->withCount([
                'records as present_count' => fn ($q) => $q->where('status', 'present'),
                'records as absent_count' => fn ($q) => $q->where('status', 'absent'),
                'records as late_count' => fn ($q) => $q->where('status', 'late'),
                'records as total_count',
            ])
            ->get()
            ->keyBy('stream_id');

        $streams = Stream::where('school_id', $schoolId)
            ->with('grade:id,name')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get();

        return $streams->map(fn ($stream) => [
            'stream_id' => $stream->id,
            'stream_name' => $stream->name,
            'grade_name' => $stream->grade?->name,
            'session' => ($s = $sessions->get($stream->id)) ? [
                'id' => $s->id,
                'present' => $s->present_count,
                'absent' => $s->absent_count,
                'late' => $s->late_count,
                'total' => $s->total_count,
                'finalized' => $s->isFinalized(),
            ] : null,
        ])->values()->toArray();
    }
}
