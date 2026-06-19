<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'grade_number',
        'level',
        'is_ecz_year',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'grade_number' => 'integer',
            'is_ecz_year' => 'boolean',
            'order_index' => 'integer',
        ];
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function gradeSubjects(): HasMany
    {
        return $this->hasMany(GradeSubject::class);
    }

    /** @return HasMany<Pupil> */
    public function pupils(): HasMany
    {
        return $this->hasMany(Pupil::class);
    }

    // Scopes

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeEczYear(Builder $query): Builder
    {
        return $query->where('is_ecz_year', 1);
    }

    // Accessors

    protected function levelLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->level) {
                'primary' => 'Primary',
                'junior_secondary' => 'Junior Secondary',
                'senior_secondary' => 'Senior Secondary',
                default => ucfirst(str_replace('_', ' ', $this->level)),
            }
        );
    }
}
