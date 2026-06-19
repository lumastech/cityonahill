<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dormitory extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'gender',
        'capacity',
        'warden_id',
        'description',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function warden(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warden_id');
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    public function getOccupancyAttribute(): int
    {
        return $this->beds()->where('status', 'occupied')->count();
    }

    public function getAvailableBedsAttribute(): int
    {
        return $this->beds()->where('status', 'available')->count();
    }
}
