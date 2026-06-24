<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasAudit;

class FeedingSession extends Model
{
    use HasFactory;
    use HasAudit;

    protected $fillable = [
        'school_id',
        'date',
        'meal_type',
        'stream_id',
        'recorded_by',
        'finalized',
    ];

    protected $casts = [
        'date' => 'date',
        'finalized' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class, 'session_id');
    }

    public function getServedCountAttribute(): int
    {
        return $this->feedingRecords()->where('served', 1)->count();
    }
}
