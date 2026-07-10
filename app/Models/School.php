<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class School extends Model implements HasMedia
{
    use HasAudit;
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'code',
        'type',
        'level',
        'province',
        'district',
        'address',
        'phone',
        'email',
        'website',
        'moe_registration_no',
        'headteacher_id',
        'owner_id',
        'established_year',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'established_year' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('documents');
    }

    // Relationships

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function headteacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'headteacher_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(SchoolSetting::class);
    }

    /** @return HasMany<Grade> */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /** @return HasMany<Stream> */
    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    /** @return HasMany<Pupil> */
    public function pupils(): HasMany
    {
        return $this->hasMany(Pupil::class);
    }

    /** @return HasMany<Staff> */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /** @return HasMany<AcademicYear> */
    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
