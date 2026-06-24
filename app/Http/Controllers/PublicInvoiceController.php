<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\InvoicePaymentLink;
use App\Services\FinanceService;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicInvoiceController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly FinanceService        $financeService,
    ) {}

    public function show(string $token): Response|RedirectResponse
    {
        $link = InvoicePaymentLink::where('token', $token)->firstOrFail();

        if ($link->isExpired()) {
            return Inertia::render('Public/Invoice', ['expired' => true]);
        }

        if ($link->opened_at === null) {
            $link->update(['opened_at' => now()]);
        }

        $invoice = $link->invoice()->with([
            'pupil:id,first_name,last_name,admission_no',
            'feeStructure:id,name',
            'term:id,name',
            'payments',
        ])->firstOrFail();

        $gateway = $this->gatewayManager->active($invoice->school_id);

        return Inertia::render('Public/Invoice', [
            'expired'        => false,
            'token'          => $token,
            'invoice'        => $invoice,
            'amount_paid'    => $invoice->amount_paid,
            'outstanding'    => $invoice->outstanding,
            'gateway_active' => $gateway !== null,
            'payer_phone'    => $link->sent_to,
        ]);
    }

    public function pay(Request $request, string $token): RedirectResponse
    {
        $link = InvoicePaymentLink::where('token', $token)->firstOrFail();
        abort_if($link->isExpired(), 410, 'This payment link has expired.');

        $invoice = $link->invoice;
        abort_if($invoice->status === 'paid', 422, 'Invoice is already paid.');

        $data = $request->validate([
            'phone'  => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $phone = InitiatePaymentController::normalisePhone($data['phone']);
        if ($phone === null) {
            return back()->withErrors(['phone' => 'Enter a valid 10-digit Zambian mobile number (e.g. 0977123456).'])->withInput();
        }

        $gateway = $this->gatewayManager->active($invoice->school_id);
        abort_if($gateway === null, 422, 'Online payment is not available right now.');

        $reference = (string) Str::uuid();
        $result    = $gateway->initiate($phone, (float) $data['amount'], $reference);

        FeePayment::create([
            'school_id'      => $invoice->school_id,
            'pupil_id'       => $invoice->pupil_id,
            'invoice_id'     => $invoice->id,
            'amount'         => $data['amount'],
            'payment_method' => 'airtel_money',
            'gateway'        => $gateway->driver(),
            'gateway_status' => $result->status,
            'payer_phone'    => $phone,
            'reference'      => $reference,
            'transaction_id' => $result->reference,
            'received_by'    => 1, // system user
            'payment_date'   => now()->toDateString(),
        ]);

        if ($result->success && $result->status === 'completed') {
            $this->financeService->reconcileInvoice($invoice);
        }

        return redirect()->route('invoices.pay', $token)->with(
            $result->status === 'failed' ? 'error' : 'success',
            $result->status === 'failed'
                ? 'Payment failed: ' . ($result->errorMessage ?? 'Please try again.')
                : 'Payment request sent! Please approve the prompt on your phone.',
        );
    }
}
