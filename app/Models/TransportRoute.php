<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasAudit;

class TransportRoute extends Model
{
    use HasFactory;
    use HasAudit;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'pickup_points',
        'vehicle_registration',
        'vehicle_type',
        'capacity',
        'driver_name',
        'driver_phone',
        'driver_user_id',
        'status',
    ];

    protected $casts = [
        'pickup_points' => 'array',
        'capacity' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function pupilTransports(): HasMany
    {
        return $this->hasMany(PupilTransport::class, 'route_id');
    }

    public function getOccupancyAttribute(): int
    {
        return $this->pupilTransports()->where('status', 'active')->count();
    }

    public function getPickupPointsAttribute(): array
    {
        return $this->attributes['pickup_points']
            ? json_decode($this->attributes['pickup_points'], true) ?? []
            : [];
    }
}
