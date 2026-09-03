<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LessonPlan extends Model implements HasMedia
{
    use HasAudit;
    use HasFactory;
    use InteractsWithMedia;

    public const ATTACHMENTS = 'lesson-plan-attachments';

    /** The stages of the lesson plan table, in the order they are taught. */
    public const STAGES = ['Introduction', 'Development', 'Application'];

    protected $fillable = [
        'school_id',
        'subject_id',
        'stream_id',
        'term_id',
        'topic',
        'sub_topic',
        'general_competence',
        'specific_competence',
        'lesson_goal',
        'reference',
        'prior_knowledge',
        'learning_material',
        'learning_environment',
        'stages',
        'conclusion',
        'evaluation',
        'week_number',
        'lesson_date',
        'duration_minutes',
        'boys_count',
        'girls_count',
        'status',
        'submitted_by',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'comment',
    ];

    /** @var list<string> */
    protected $appends = ['total_pupils'];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'stages' => 'array',
        ];
    }

    /** @return array<int, array<string, string|null>> */
    public static function blankStages(): array
    {
        return array_map(fn (string $stage) => [
            'stage' => $stage,
            'teacher_activity' => null,
            'learner_activity' => null,
            'assessment_criteria' => null,
        ], self::STAGES);
    }

    // Attributes

    protected function totalPupils(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->boys_count === null && $this->girls_count === null) {
                return null;
            }

            return (int) $this->boys_count + (int) $this->girls_count;
        });
    }

    /**
     * Accepted file types are enforced by request validation (StoreLessonPlanData), which
     * reports a mismatch to the user instead of throwing from the media library.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ATTACHMENTS);
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'submitted');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('submitted_by', $userId);
    }
}
