<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class MtnSmsService
{
    public function send(string $phone, string $message): array
    {
        $response = Http::post(config('services.mtn.api_url'), [
            'phone' => $phone,
            'message' => $message,
            'api_key' => config('services.mtn.api_key'),
        ]);

        return [
            'success' => $response->successful(),
            'external_message_id' => $response->json('message_id'),
        ];
    }
}
