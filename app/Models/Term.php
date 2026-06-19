<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'number',
        'start_date',
        'end_date',
        'is_current',
        'ca_deadline',
        'exam_start',
        'exam_end',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'ca_deadline' => 'date',
            'exam_start' => 'date',
            'exam_end' => 'date',
            'is_current' => 'boolean',
            'number' => 'integer',
        ];
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(SchoolHoliday::class)->orderBy('start_date');
    }

    // Scopes

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', 1);
    }

    // Accessors

    protected function weeks(): Attribute
    {
        return Attribute::make(
            get: function () {
                $holidays = $this->relationLoaded('holidays')
                    ? $this->holidays
                    : $this->holidays()->get();

                $holidayDays = $holidays->sum(function (SchoolHoliday $holiday) {
                    $start = $holiday->start_date->max($this->start_date);
                    $end = $holiday->end_date->min($this->end_date);

                    return $start->lte($end) ? $start->diffInDays($end) + 1 : 0;
                });

                $totalDays = $this->start_date->diffInDays($this->end_date);

                return (int) ceil(max(0, $totalDays - $holidayDays) / 7);
            }
        );
    }

    // Methods

    public function isRegistrationOpen(): bool
    {
        $today = now()->toDateString();
        $deadline = ($this->ca_deadline ?? $this->end_date)->toDateString();

        return $today >= $this->start_date->toDateString() && $today <= $deadline;
    }
}
