<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EczCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'pupil_id',
        'exam_year',
        'grade_level',
        'index_number',
        'centre_number',
        'registration_status',
        'division',
        'total_points',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    public function subjectEntries(): HasMany
    {
        return $this->hasMany(EczSubjectEntry::class, 'candidate_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(EczResult::class, 'candidate_id');
    }

    public function scopeForExamYear(Builder $query, int $year): Builder
    {
        return $query->where('exam_year', $year);
    }

    public function scopeForGradeLevel(Builder $query, int $level): Builder
    {
        return $query->where('grade_level', $level);
    }

    public function computeDivision(): string
    {
        $entries = $this->subjectEntries()
            ->whereNotNull('actual_points')
            ->orderBy('actual_points')
            ->get();

        if ($entries->isEmpty()) {
            return '—';
        }

        $limit = $this->grade_level === 7 ? 4 : 6;
        $bestPoints = $entries->take($limit)->sum('actual_points');

        return match (true) {
            $bestPoints <= 24 => 'Division I',
            $bestPoints <= 36 => 'Division II',
            $bestPoints <= 48 => 'Division III',
            $bestPoints <= 54 => 'Division IV',
            default => 'Fail',
        };
    }

    public function getPredictedDivisionAttribute(): string
    {
        $eczPoints = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 9, 'U' => 9, 'X' => 9];

        $entries = $this->subjectEntries()
            ->whereNotNull('predicted_grade')
            ->get();

        if ($entries->isEmpty()) {
            return '—';
        }

        $limit = $this->grade_level === 7 ? 4 : 6;

        $points = $entries
            ->map(fn ($e) => $eczPoints[strtoupper($e->predicted_grade)] ?? 9)
            ->sort()
            ->take($limit)
            ->sum();

        return match (true) {
            $points <= 24 => 'Division I',
            $points <= 36 => 'Division II',
            $points <= 48 => 'Division III',
            $points <= 54 => 'Division IV',
            default => 'Fail',
        };
    }
}
