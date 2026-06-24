<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasAudit;

class FeedingRecord extends Model
{
    use HasFactory;
    use HasAudit;

    protected $fillable = [
        'session_id',
        'pupil_id',
        'served',
    ];

    protected $casts = [
        'served' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(FeedingSession::class, 'session_id');
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }
}
