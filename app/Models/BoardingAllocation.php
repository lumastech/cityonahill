<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardingAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'pupil_id',
        'bed_id',
        'term_id',
        'allocated_date',
        'vacated_date',
        'fee_amount',
        'status',
    ];

    protected $casts = [
        'allocated_date' => 'date',
        'vacated_date' => 'date',
        'fee_amount' => 'float',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
