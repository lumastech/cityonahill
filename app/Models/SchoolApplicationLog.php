<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolApplicationLog extends Model
{
    protected $fillable = [
        'application_id',
        'actor_id',
        'action',
        'notes',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(SchoolApplication::class, 'application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
