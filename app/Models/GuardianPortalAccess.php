<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianPortalAccess extends Model
{
    protected $table = 'guardian_portal_access';

    protected $fillable = [
        'guardian_id',
        'user_id',
        'activated_at',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
