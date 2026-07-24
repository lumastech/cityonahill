<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasAudit;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'category',
        'is_zambian_language',
        'is_ecz_subject',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_zambian_language' => 'boolean',
            'is_ecz_subject' => 'boolean',
        ];
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function gradeSubjects(): HasMany
    {
        return $this->hasMany(GradeSubject::class);
    }

    /** @return HasMany<SubjectLearningContent> */
    public function learningContents(): HasMany
    {
        return $this->hasMany(SubjectLearningContent::class)->orderBy('sort_order');
    }

    /** @return HasMany<Assessment> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /** @return HasMany<TermResult> */
    public function termResults(): HasMany
    {
        return $this->hasMany(TermResult::class);
    }

    /** @return HasMany<EczSubjectEntry> */
    public function eczSubjectEntries(): HasMany
    {
        return $this->hasMany(EczSubjectEntry::class);
    }

    // Scopes

    public function scopeEcz(Builder $query): Builder
    {
        return $query->where('is_ecz_subject', 1);
    }

    public function scopeZambianLanguage(Builder $query): Builder
    {
        return $query->where('is_zambian_language', 1);
    }
}
