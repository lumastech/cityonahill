<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'sender_id',
        'recipient_id',
        'pupil_id',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    public function scopeThread(Builder $query, int $a, int $b): Builder
    {
        return $query->where(function ($q) use ($a, $b) {
            $q->where('sender_id', $a)->where('recipient_id', $b);
        })->orWhere(function ($q) use ($a, $b) {
            $q->where('sender_id', $b)->where('recipient_id', $a);
        });
    }
}
