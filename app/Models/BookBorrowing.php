<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookBorrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'book_id', 'borrower_type', 'borrower_id',
        'borrowed_date', 'due_date', 'returned_date',
        'fine_amount', 'status', 'issued_by', 'returned_to',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_date' => 'date',
            'due_date' => 'date',
            'returned_date' => 'date',
            'fine_amount' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returnedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    public function borrower(): Pupil|Staff|null
    {
        return match ($this->borrower_type) {
            'pupil' => Pupil::find($this->borrower_id),
            'staff' => Staff::find($this->borrower_id),
            default => null,
        };
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', 'borrowed');
    }

    public function getFineDueAttribute(): float
    {
        if ($this->status !== 'borrowed' || ! $this->due_date) {
            return 0.0;
        }

        $daysOverdue = max(0, now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false) * -1);
        $fineRate = (float) SchoolSetting::get($this->school_id, 'library_fine_per_day', 0.50);

        return round($daysOverdue * $fineRate, 2);
    }
}
