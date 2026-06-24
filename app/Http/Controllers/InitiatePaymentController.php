<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Services\FinanceService;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InitiatePaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly FinanceService        $financeService,
    ) {}

    /** Strip non-digits, handle Zambia country code, return 10-digit local format or null. */
    public static function normalisePhone(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);

        if (str_starts_with($digits, '260')) {
            $digits = '0' . substr($digits, 3);
        }

        return strlen($digits) === 10 ? $digits : null;
    }

    public function __invoke(Request $request, FeeInvoice $feeInvoice): RedirectResponse
    {
        $school = app('current_school');
        abort_if($feeInvoice->school_id !== $school->id, 403);
        abort_if($feeInvoice->status === 'paid', 422, 'Invoice is already paid.');

        $this->authorize('fee.collect');

        $data = $request->validate([
            'phone'  => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $feeInvoice->balance_due],
        ]);

        $phone = self::normalisePhone($data['phone']);
        if ($phone === null) {
            return back()->withErrors(['phone' => 'Enter a valid 10-digit Zambian mobile number (e.g. 0977123456).'])->withInput();
        }

        $gateway = $this->gatewayManager->active($school->id);
        abort_if($gateway === null, 422, 'No payment gateway is configured.');

        $reference = (string) Str::uuid();

        $result = $gateway->initiate(
            phone:     $phone,
            amount:    (float) $data['amount'],
            reference: $reference,
        );

        // Persist the payment record regardless of sync/async outcome
        $payment = FeePayment::create([
            'school_id'              => $school->id,
            'pupil_id'               => $feeInvoice->pupil_id,
            'invoice_id'             => $feeInvoice->id,
            'amount'                 => $data['amount'],
            'payment_method'         => 'airtel_money', // gateway handles mobile money
            'gateway'                => $gateway->driver(),
            'gateway_status'         => $result->status,
            'payer_phone'            => $phone,
            'reference'              => $reference,
            'transaction_id'         => $result->reference,
            'received_by'            => auth()->id(),
            'payment_date'           => now()->toDateString(),
        ]);

        if ($result->success && $result->status === 'completed') {
            // IZB synchronous — update invoice status immediately
            $this->financeService->reconcileInvoice($feeInvoice);

            return redirect()
                ->route('fee-invoices.show', $feeInvoice)
                ->with('success', 'Payment of ZMW ' . number_format($data['amount'], 2) . ' confirmed.');
        }

        if ($result->status === 'pending') {
            // Lenco async — awaiting webhook
            return redirect()
                ->route('fee-invoices.show', $feeInvoice)
                ->with('info', 'Payment request sent. The customer will receive a prompt on their phone. Status will update automatically.');
        }

        // Failed
        $payment->delete();

        return redirect()
            ->route('fee-invoices.show', $feeInvoice)
            ->with('error', 'Payment failed: ' . ($result->errorMessage ?? 'Unknown error.'));
    }
}
