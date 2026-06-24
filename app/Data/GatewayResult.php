<?php

namespace App\Data;

readonly class GatewayResult
{
    public function __construct(
        public bool    $success,
        public string  $reference,
        public string  $status,       // pending | completed | failed
        public ?string $errorMessage = null,
    ) {}
}
