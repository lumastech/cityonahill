<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSlot extends Model
{
    protected $fillable = [
        'school_id',
        'stream_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'period_number',
        'start_time',
        'end_time',
        'room',
        'term_id',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week'   => 'integer',
            'period_number' => 'integer',
        ];
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Scopes

    public function scopeForStream(Builder $query, int $streamId): Builder
    {
        return $query->where('stream_id', $streamId);
    }

    public function scopeForDay(Builder $query, int $day): Builder
    {
        return $query->where('day_of_week', $day);
    }

    // Helpers

    public static function detectConflict(int $streamId, int $day, int $period, ?int $excludeId = null): bool
    {
        return static::where('stream_id', $streamId)
            ->where('day_of_week', $day)
            ->where('period_number', $period)
            ->when($excludeId !== null, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
