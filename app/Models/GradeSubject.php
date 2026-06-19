<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSubject extends Model
{
    protected $fillable = [
        'school_id',
        'grade_id',
        'subject_id',
        'is_core',
        'ca_weight',
        'exam_weight',
    ];

    protected function casts(): array
    {
        return [
            'is_core'     => 'boolean',
            'ca_weight'   => 'integer',
            'exam_weight' => 'integer',
        ];
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // Scopes

    public function scopeCore(Builder $query): Builder
    {
        return $query->where('is_core', 1);
    }
}
