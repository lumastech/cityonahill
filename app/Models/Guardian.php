<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'first_name',
        'last_name',
        'relationship',
        'phone',
        'phone2',
        'email',
        'nrc',
        'occupation',
        'employer',
        'address',
    ];

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function portalAccess(): HasOne
    {
        return $this->hasOne(GuardianPortalAccess::class);
    }

    public function pupils(): BelongsToMany
    {
        return $this->belongsToMany(Pupil::class, 'pupil_guardians')
            ->withPivot('is_primary', 'is_emergency', 'can_pickup')
            ->withTimestamps();
    }

    // Accessors

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Methods

    public function hasPupilsAtSchool(int $schoolId): bool
    {
        return $this->pupils()->where('pupils.school_id', $schoolId)->exists();
    }
}
