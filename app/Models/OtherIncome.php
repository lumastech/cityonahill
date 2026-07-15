<?php

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OtherIncome extends Model implements HasMedia
{
    use HasAudit;
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'school_id', 'source', 'description', 'amount',
        'received_date', 'recorded_by', 'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'received_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipts')->singleFile();
    }
}
