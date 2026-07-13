<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasAudit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AssessmentScore extends Model implements HasMedia
{
    use HasFactory;
    use HasAudit;
    use InteractsWithMedia;

    public const ANSWER_SHEETS = 'answer-sheets';

    protected $fillable = [
        'assessment_id',
        'pupil_id',
        'marks_obtained',
        'grade_letter',
        'remarks',
        'entered_by',
        'entered_at',
    ];

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'decimal:2',
            'entered_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    /**
     * Accepted file types are enforced by request validation (EnterScoresData), which
     * reports a mismatch to the user instead of throwing from the media library.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ANSWER_SHEETS);
    }
}
