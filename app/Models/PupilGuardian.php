<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PupilGuardian extends Pivot
{
    protected $table = 'pupil_guardians';

    public $timestamps = true;

    protected $fillable = [
        'pupil_id',
        'guardian_id',
        'is_primary',
        'is_emergency',
        'can_pickup',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_emergency' => 'boolean',
            'can_pickup' => 'boolean',
        ];
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
