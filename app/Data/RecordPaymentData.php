<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class RecordPaymentData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('fee_invoices', 'id')]
        public readonly int $invoice_id,

        #[Required, Min(0.01)]
        public readonly float $amount,

        #[Required, In(['cash', 'airtel_money', 'mtn_momo', 'bank_transfer', 'cheque'])]
        public readonly string $payment_method,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $reference = null,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $transaction_id = null,

        #[Required, Date]
        public readonly string $payment_date = '',

        #[Nullable, StringType, Max(20)]
        public readonly ?string $mobile_money_provider = null,
    ) {}
}
