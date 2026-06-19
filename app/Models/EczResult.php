<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EczResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'candidate_id',
        'published_at',
        'raw_result_file',
        'entry_method',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(EczCandidate::class, 'candidate_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
