<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasAudit;

class AttendanceRecord extends Model
{
    use HasFactory;
    use HasAudit;

    protected $fillable = [
        'session_id',
        'pupil_id',
        'status',
        'remarks',
    ];

    // Relationships

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    // Scopes

    public function scopeAbsent(Builder $query): Builder
    {
        return $query->where('status', 'absent');
    }

    public function scopePresent(Builder $query): Builder
    {
        return $query->where('status', 'present');
    }
}
