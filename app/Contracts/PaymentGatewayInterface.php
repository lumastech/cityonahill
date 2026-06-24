<?php

namespace App\Contracts;

use App\Data\GatewayResult;

interface PaymentGatewayInterface
{
    /** Initiate a mobile money charge. Returns result immediately (IZB) or after webhook (Lenco). */
    public function initiate(string $phone, float $amount, string $reference): GatewayResult;

    /** Verify a transaction by gateway reference — used by async webhooks. */
    public function verify(string $gatewayReference): GatewayResult;

    /** Driver identifier stored on the payment record. */
    public function driver(): string;
}
