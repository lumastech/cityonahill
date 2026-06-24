<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\School;
use App\Services\FinanceService;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LencoWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly FinanceService        $financeService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $signature = $request->header('X-Lenco-Signature', '');
        $payload   = $request->getContent();
        $data      = $request->all();
        $reference = $data['data']['reference'] ?? null;

        Log::info('Lenco webhook received', ['event' => $data['event'] ?? null, 'reference' => $reference]);

        if (! $reference) {
            return response()->json(['status' => 'ignored']);
        }

        // Find the payment — use its school to verify the signature
        $payment = FeePayment::where('reference', $reference)
            ->whereIn('gateway_status', ['pending'])
            ->with('invoice')
            ->first();

        if (! $payment) {
            return response()->json(['status' => 'not_found']);
        }

        $lenco = $this->gatewayManager->lencoForSchool($payment->school_id);

        if ($lenco && ! $lenco->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Lenco webhook signature mismatch', ['school_id' => $payment->school_id]);

            return response()->json(['status' => 'invalid_signature'], 400);
        }

        $event = $data['event'] ?? '';

        if ($event === 'collection.successful') {
            $payment->update([
                'gateway_status' => 'completed',
                'transaction_id' => $data['data']['id'] ?? $payment->transaction_id,
            ]);
            $this->financeService->reconcileInvoice($payment->invoice);
        } elseif ($event === 'collection.failed') {
            $payment->update(['gateway_status' => 'failed']);
        }

        return response()->json(['status' => 'ok']);
    }
}
