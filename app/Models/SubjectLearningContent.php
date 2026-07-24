<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SubjectLearningContent extends Model implements HasMedia
{
    use HasAudit;
    use HasFactory;
    use InteractsWithMedia;

    public const MEDIA = 'learning-materials';

    protected $fillable = [
        'school_id',
        'subject_id',
        'grade_id',
        'title',
        'body',
        'sort_order',
    ];

    /**
     * Accepted file types are enforced by request validation (StoreSubjectContentData), which
     * reports a mismatch to the user instead of throwing from the media library.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }
}
