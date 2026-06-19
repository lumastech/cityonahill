<?php

namespace App\Services\Sms;

use InvalidArgumentException;

class SmsServiceFactory
{
    public static function make(?string $provider = null): AirtelSmsService|MtnSmsService
    {
        $provider = $provider ?? config('services.sms.default_provider', 'airtel');

        return match ($provider) {
            'airtel' => new AirtelSmsService,
            'mtn' => new MtnSmsService,
            default => throw new InvalidArgumentException("Unsupported SMS provider: {$provider}"),
        };
    }
}
