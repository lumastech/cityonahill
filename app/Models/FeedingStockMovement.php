<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedingStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'stock_id',
        'type',
        'quantity',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(FeedingStock::class, 'stock_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
