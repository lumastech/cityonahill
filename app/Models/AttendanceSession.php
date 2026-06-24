<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasAudit;

class AttendanceSession extends Model
{
    use HasFactory;
    use HasAudit;

    protected $fillable = [
        'school_id',
        'stream_id',
        'term_id',
        'date',
        'session_type',
        'recorded_by',
        'finalized',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'finalized' => 'boolean',
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

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }

    // Scopes

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForStream(Builder $query, int $streamId): Builder
    {
        return $query->where('stream_id', $streamId);
    }

    // Methods

    public function isFinalized(): bool
    {
        return (bool) $this->finalized;
    }
}
