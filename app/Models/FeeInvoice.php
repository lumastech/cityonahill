<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasAudit;

class FeeInvoice extends Model
{
    use HasFactory;
    use HasAudit;

    protected $fillable = [
        'school_id', 'pupil_id', 'fee_structure_id', 'term_id', 'academic_year_id',
        'notes', 'amount', 'discount', 'balance_due', 'due_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function pupil(): BelongsTo
    {
        return $this->belongsTo(Pupil::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'invoice_id');
    }

    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments()
            ->where(function ($q) {
                $q->whereNull('gateway_status')
                  ->orWhere('gateway_status', 'completed');
            })
            ->sum('amount');
    }

    public function getOutstandingAttribute(): float
    {
        return max(0.0, (float) $this->balance_due - $this->amount_paid);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePartial(Builder $query): Builder
    {
        return $query->where('status', 'partial');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now()->toDateString());
    }
}
