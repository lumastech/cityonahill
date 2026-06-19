<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class AirtelSmsService
{
    public function send(string $phone, string $message): array
    {
        $response = Http::post(config('services.airtel.api_url'), [
            'phone' => $phone,
            'message' => $message,
            'api_key' => config('services.airtel.api_key'),
        ]);

        return [
            'success' => $response->successful(),
            'external_message_id' => $response->json('message_id'),
        ];
    }
}
