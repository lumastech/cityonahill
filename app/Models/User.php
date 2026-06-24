<?php

namespace App\Models;

use App\Enums\SexEnum;
use App\Enums\StatusEnum;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\HasAudit;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use InteractsWithMedia;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasAudit;

    protected $fillable = [
        'name',
        'other_name',
        'email',
        'password',
        'sex',
        'phone',
        'nrc',
        'dob',
        'nationality',
        'address',
        'status',
        'school_id',
        'is_parent',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'is_parent' => 'boolean',
            'sex' => SexEnum::class,
            'status' => StatusEnum::class,
        ];
    }

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasOne<Staff> */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /** @return HasOne<Subscription> */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /** @return HasMany<SchoolApplication> */
    public function schoolApplications(): HasMany
    {
        return $this->hasMany(SchoolApplication::class, 'applicant_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile-photo')->singleFile();
        $this->addMediaCollection('documents');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->performOnCollections('profile-photo');
    }
}
