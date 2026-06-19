<?php

namespace App\Http\Controllers;

use App\Data\RecordPaymentData;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;

class FeePaymentController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function __invoke(RecordPaymentData $data): RedirectResponse
    {
        $payment = $this->financeService->recordPayment($data, auth()->id());

        return redirect()
            ->route('fee-invoices.show', $payment->invoice_id)
            ->with('success', 'Payment recorded.');
    }
}
