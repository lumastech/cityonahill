<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSetting extends Model
{
    protected $fillable = ['school_id', 'key', 'value'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public static function get(int $schoolId, string $key, mixed $default = null): mixed
    {
        return static::where('school_id', $schoolId)
            ->where('key', $key)
            ->value('value') ?? $default;
    }
}
