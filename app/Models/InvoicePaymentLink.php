<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePaymentLink extends Model
{
    protected $fillable = ['invoice_id', 'token', 'expires_at', 'sent_via', 'sent_to', 'opened_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'opened_at'  => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
