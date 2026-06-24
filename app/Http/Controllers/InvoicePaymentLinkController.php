<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\InvoicePaymentLink;
use App\Models\SmsLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoicePaymentLinkController extends Controller
{
    public function __invoke(Request $request, FeeInvoice $feeInvoice): RedirectResponse
    {
        $school = app('current_school');
        abort_if($feeInvoice->school_id !== $school->id, 403);
        abort_if($feeInvoice->status === 'paid', 422, 'Invoice is already paid.');

        $this->authorize('fee.collect');

        $data = $request->validate([
            'sent_via'      => ['required', 'in:sms,email,copy'],
            'sent_to'       => ['required_unless:sent_via,copy', 'nullable', 'string', 'max:100'],
            'expires_hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);

        $token = Str::random(64);

        InvoicePaymentLink::create([
            'invoice_id' => $feeInvoice->id,
            'token'      => $token,
            'expires_at' => now()->addHours((int) $data['expires_hours']),
            'sent_via'   => $data['sent_via'],
            'sent_to'    => $data['sent_to'] ?? null,
        ]);

        $payUrl = route('invoices.pay', $token);

        if ($data['sent_via'] === 'copy') {
            return back()->with('link_url', $payUrl);
        }

        if ($data['sent_via'] === 'sms') {
            $feeInvoice->loadMissing('pupil:id,first_name,last_name', 'feeStructure:id,name');
            $pupilName  = $feeInvoice->pupil?->first_name . ' ' . $feeInvoice->pupil?->last_name;
            $feeName    = $feeInvoice->feeStructure?->name ?? 'school fees';
            $amount     = number_format((float) $feeInvoice->balance_due, 2);
            $message    = "Pay {$feeName} (ZMW {$amount}) for {$pupilName}: {$payUrl}";

            SmsLog::create([
                'school_id'  => $school->id,
                'phone'      => $data['sent_to'],
                'message'    => $message,
                'status'     => 'pending',
                'sent_by'    => auth()->id(),
            ]);

            // Dispatch SMS via existing SMS job/service if wired up
            // SendSmsJob::dispatch($data['sent_to'], $message);
        }
        // Email: can wire up a Mailable here later

        return back()->with('success', 'Payment link sent to ' . $data['sent_to'] . '.');
    }
}
