<?php

namespace App\Models;

use App\Enums\SexEnum;
use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pupil extends Model implements HasMedia
{
    use HasAudit;
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'school_id',
        'admission_no',
        'first_name',
        'last_name',
        'other_name',
        'sex',
        'dob',
        'place_of_birth',
        'nationality',
        'religion',
        'tribe',
        'disability',
        'disability_details',
        'blood_group',
        'previous_school',
        'date_of_admission',
        'grade_id',
        'stream_id',
        'academic_year_id',
        'status',
        'transfer_school',
        'transfer_date',
    ];

    protected function casts(): array
    {
        return [
            'sex' => SexEnum::class,
            'dob' => 'date',
            'date_of_admission' => 'date',
            'transfer_date' => 'date',
        ];
    }

    protected $appends = ['full_name', 'age'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile-photo')->singleFile();
        $this->addMediaCollection('documents');
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'pupil_guardians')
            ->withPivot('is_primary', 'is_emergency', 'can_pickup')
            ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function termResults(): HasMany
    {
        return $this->hasMany(TermResult::class);
    }

    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(PupilTransfer::class);
    }

    public function eczCandidate(): HasOne
    {
        return $this->hasOne(EczCandidate::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForGrade(Builder $query, int $gradeId): Builder
    {
        return $query->where('grade_id', $gradeId);
    }

    public function scopeForStream(Builder $query, int $streamId): Builder
    {
        return $query->where('stream_id', $streamId);
    }

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    // Accessors

    public function getPrimaryGuardianAttribute(): ?Guardian
    {
        return $this->guardians->first(fn (Guardian $g) => (bool) $g->pivot->is_primary);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->other_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->dob?->age;
    }

    // Static helpers

    public static function generateAdmissionNo(int $schoolId, int $year): string
    {
        $school = School::findOrFail($schoolId);

        $sequence = static::where('school_id', $schoolId)
            ->whereYear('date_of_admission', $year)
            ->count() + 1;

        return sprintf('%s/%d/%04d', strtoupper($school->code), $year, $sequence);
    }
}
