<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedingStock extends Model
{
    use HasFactory;

    protected $table = 'feeding_stock';

    protected $fillable = [
        'school_id',
        'item_name',
        'unit',
        'quantity_on_hand',
        'reorder_level',
        'last_restocked_at',
        'cost_per_unit',
    ];

    protected $casts = [
        'quantity_on_hand' => 'float',
        'reorder_level' => 'float',
        'cost_per_unit' => 'float',
        'last_restocked_at' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FeedingStockMovement::class, 'stock_id');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
    }
}
