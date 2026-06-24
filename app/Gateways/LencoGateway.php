<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Data\GatewayResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LencoGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiToken,
        private readonly string $webhookHash = '',
    ) {}

    public function driver(): string
    {
        return 'lenco';
    }

    public function initiate(string $phone, float $amount, string $reference): GatewayResult
    {
        $local    = $this->toLocal($phone);
        $mobile   = $this->toIntl($local);   // Lenco requires 260XXXXXXXXX international format
        $operator = $this->detectOperator($local);

        if ($operator === null) {
            return new GatewayResult(false, $reference, 'failed', 'Unrecognised mobile operator from phone number.');
        }

        try {
            Log::info('Lenco initiate', [
                'phone'    => $mobile,
                'operator' => $operator,
                'amount'   => $amount,
                'ref'      => $reference,
            ]);

            $response = Http::withoutVerifying()
                ->withToken($this->apiToken)
                ->post("{$this->baseUrl}/collections/mobile-money", [
                    'amount'       => $amount,
                    'currency'     => 'ZMW',
                    'phone_number' => $mobile,
                    'operator'     => $operator,
                    'reference'    => $reference,
                ]);

            Log::info('Lenco response', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->successful()) {
                // Lenco is async: 202 accepted, webhook will confirm
                return new GatewayResult(true, $reference, 'pending');
            }

            Log::error('Lenco initiation failed', ['body' => $response->body()]);

            return new GatewayResult(false, $reference, 'failed', $response->json('message') ?? 'Lenco request rejected.');
        } catch (\Throwable $e) {
            Log::error('Lenco exception', ['error' => $e->getMessage()]);

            return new GatewayResult(false, $reference, 'failed', $e->getMessage());
        }
    }

    public function verify(string $gatewayReference): GatewayResult
    {
        try {
            $response = Http::withoutVerifying()
                ->withToken($this->apiToken)
                ->get("{$this->baseUrl}/collections/status/{$gatewayReference}");

            if ($response->successful()) {
                $status = $response->json('data.status') ?? 'pending';
                $success = in_array($status, ['successful', 'completed']);

                return new GatewayResult($success, $gatewayReference, $success ? 'completed' : $status);
            }
        } catch (\Throwable $e) {
            Log::error('Lenco verify exception', ['error' => $e->getMessage()]);
        }

        return new GatewayResult(false, $gatewayReference, 'failed', 'Could not verify Lenco transaction.');
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->webhookHash)) {
            return true;
        }

        return hash_equals(hash_hmac('sha512', $payload, $this->webhookHash), $signature);
    }

    /** Strip non-digits and normalise to 10-digit local format (0XXXXXXXXX). */
    private function toLocal(string $input): string
    {
        $digits = preg_replace('/\D/', '', $input);

        if (str_starts_with($digits, '260')) {
            return '0' . substr($digits, 3);
        }

        return $digits;
    }

    /** Convert local 10-digit format to Lenco's required 12-digit international format. */
    private function toIntl(string $local): string
    {
        return '260' . substr($local, 1);
    }

    /** Operator detection on local format — position 2 is the operator digit. */
    private function detectOperator(string $local): ?string
    {
        return match (substr($local, 2, 1)) {
            '7' => 'airtel',
            '6' => 'mtn',
            '5' => 'zamtel',
            default => null,
        };
    }
}
