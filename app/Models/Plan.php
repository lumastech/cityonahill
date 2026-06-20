<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price_per_school',
        'billing_cycle',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_school' => 'decimal:2',
            'features'         => 'array',
            'is_active'        => 'boolean',
        ];
    }

    /** @return HasMany<Subscription> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
