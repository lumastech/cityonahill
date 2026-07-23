<?php

namespace App\Services;

use App\Data\LinkSubjectToGradeData;
use App\Data\StoreGradeData;
use App\Data\StoreStreamData;
use App\Data\StoreSubjectData;
use App\Data\StoreTimetableSlotData;
use App\Exceptions\ConflictException;
use App\Models\Grade;
use App\Models\GradeSubject;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;

class ClassStructureService
{
    /**
     * @throws ConflictException
     */
    public function createGrade(int $schoolId, StoreGradeData $data): Grade
    {
        $this->guardUniqueGradeNumber($schoolId, $data->grade_number);

        return Grade::create([
            'school_id' => $schoolId,
            'name' => $data->name,
            'grade_number' => $data->grade_number,
            'level' => $data->level,
            'is_ecz_year' => $data->is_ecz_year,
            'order_index' => $data->order_index,
        ]);
    }

    /**
     * @throws ConflictException
     */
    public function updateGrade(Grade $grade, StoreGradeData $data): Grade
    {
        $this->guardUniqueGradeNumber($grade->school_id, $data->grade_number, $grade->id);

        $grade->update([
            'name' => $data->name,
            'grade_number' => $data->grade_number,
            'level' => $data->level,
            'is_ecz_year' => $data->is_ecz_year,
            'order_index' => $data->order_index,
        ]);

        return $grade->fresh();
    }

    /**
     * @throws ConflictException
     */
    private function guardUniqueGradeNumber(int $schoolId, int $gradeNumber, ?int $ignoreId = null): void
    {
        $exists = Grade::forSchool($schoolId)
            ->where('grade_number', $gradeNumber)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw new ConflictException(
                "Grade number {$gradeNumber} is already used by another grade at this school. Each grade must have a unique number (pre-primary classes can use negatives, e.g. -3, -2, -1)."
            );
        }
    }

    public function createStream(int $schoolId, StoreStreamData $data): Stream
    {
        return Stream::create([
            'school_id' => $schoolId,
            'grade_id' => $data->grade_id,
            'name' => $data->name,
            'class_teacher_id' => $data->class_teacher_id,
            'academic_year_id' => $data->academic_year_id,
            'capacity' => $data->capacity,
        ]);
    }

    public function createSubject(int $schoolId, StoreSubjectData $data): Subject
    {
        return Subject::create([
            'school_id' => $schoolId,
            'name' => $data->name,
            'code' => $data->code,
            'category' => $data->category,
            'is_zambian_language' => $data->is_zambian_language,
            'is_ecz_subject' => $data->is_ecz_subject,
            'description' => $data->description,
        ]);
    }

    public function linkSubjectToGrade(int $schoolId, LinkSubjectToGradeData $data): GradeSubject
    {
        return GradeSubject::updateOrCreate(
            [
                'school_id' => $schoolId,
                'grade_id' => $data->grade_id,
                'subject_id' => $data->subject_id,
            ],
            [
                'is_core' => $data->is_core,
                'ca_weight' => $data->ca_weight,
                'exam_weight' => $data->exam_weight,
            ]
        );
    }

    public function unlinkSubjectFromGrade(int $gradeSubjectId): void
    {
        GradeSubject::findOrFail($gradeSubjectId)->delete();
    }

    public function assignClassTeacher(int $streamId, int $teacherId): Stream
    {
        $stream = Stream::findOrFail($streamId);
        $stream->update(['class_teacher_id' => $teacherId]);

        return $stream->fresh(['classTeacher']);
    }

    /**
     * @throws ConflictException
     */
    public function createTimetableSlot(int $schoolId, StoreTimetableSlotData $data): TimetableSlot
    {
        if (TimetableSlot::detectConflict($data->stream_id, $data->day_of_week, $data->period_number)) {
            throw new ConflictException(
                "Period {$data->period_number} on day {$data->day_of_week} is already occupied for this class."
            );
        }

        return TimetableSlot::create([
            'school_id' => $schoolId,
            'stream_id' => $data->stream_id,
            'subject_id' => $data->subject_id,
            'teacher_id' => $data->teacher_id,
            'day_of_week' => $data->day_of_week,
            'period_number' => $data->period_number,
            'start_time' => $data->start_time,
            'end_time' => $data->end_time,
            'room' => $data->room,
        ]);
    }

    public function getStreamTimetable(int $streamId): Collection
    {
        return TimetableSlot::with(['subject', 'teacher'])
            ->forStream($streamId)
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get()
            ->groupBy('day_of_week');
    }

    public function getTeacherTimetable(int $teacherId): Collection
    {
        return TimetableSlot::with(['subject', 'stream.grade'])
            ->where('teacher_id', $teacherId)
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get()
            ->groupBy('day_of_week');
    }
}
