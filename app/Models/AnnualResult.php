<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'pupil_id',
        'academic_year_id',
        'total_marks',
        'average_marks',
        'position_in_stream',
        'grade_stream_id',
        'promoted',
        'headteacher_comment',
    ];

    protected function casts(): array
    {
        return [
            'total_marks' => 'decimal:2',
            'average_marks' => 'decimal:2',
            'promoted' => 'boolean',
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeStream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'grade_stream_id');
    }
}
