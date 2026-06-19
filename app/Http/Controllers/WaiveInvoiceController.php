<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WaiveInvoiceController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function __invoke(Request $request, FeeInvoice $feeInvoice): RedirectResponse
    {
        abort_if($feeInvoice->school_id !== app('current_school')?->id, 403);

        $reason = $request->string('reason')->toString();

        $this->financeService->waiveInvoice($feeInvoice->id, $reason, auth()->id());

        return back()->with('success', 'Invoice waived.');
    }
}
