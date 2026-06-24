<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasAudit;

class TermResult extends Model
{
    use HasFactory;
    use HasAudit;

    protected $fillable = [
        'school_id',
        'pupil_id',
        'subject_id',
        'term_id',
        'academic_year_id',
        'stream_id',
        'ca_marks',
        'exam_marks',
        'total_marks',
        'grade_letter',
        'points',
        'position_in_stream',
        'teacher_comment',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'ca_marks' => 'decimal:2',
            'exam_marks' => 'decimal:2',
            'total_marks' => 'decimal:2',
            'published' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', 1);
    }

    public function scopeForPupil(Builder $query, int $pupilId): Builder
    {
        return $query->where('pupil_id', $pupilId);
    }

    public static function computeGradeLetter(float $total): string
    {
        foreach (config('skuu.grading_scale') as $band) {
            if ($total >= $band['min'] && $total <= $band['max']) {
                return $band['letter'];
            }
        }

        return 'F';
    }

    public static function computePoints(string $letter): int
    {
        return match (strtoupper($letter)) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            default => 9,
        };
    }
}
