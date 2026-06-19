<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EczSubjectEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'subject_id',
        'entered_by',
        'predicted_grade',
        'actual_grade',
        'actual_points',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(EczCandidate::class, 'candidate_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
