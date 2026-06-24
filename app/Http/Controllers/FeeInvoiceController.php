<?php

namespace App\Http\Controllers;

use App\Data\BulkRaiseInvoicesData;
use App\Data\RaiseInvoiceData;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Term;
use App\Services\PaymentGatewayManager;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeeInvoiceController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');
        $gradeId = $request->integer('grade_id') ?: null;
        $termId = $request->integer('term_id') ?: null;
        $status = $request->string('status')->toString() ?: null;

        $query = FeeInvoice::where('school_id', $school->id)
            ->with([
                'pupil:id,first_name,last_name,admission_no,grade_id',
                'pupil.grade:id,grade_number',
                'feeStructure:id,name',
                'term:id,name',
            ]);

        if ($gradeId) {
            $query->whereHas('pupil', fn ($q) => $q->where('grade_id', $gradeId));
        }

        if ($termId) {
            $query->where('term_id', $termId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return Inertia::render('Finance/Invoices/Index', [
            'invoices' => $query->paginate(30)->withQueryString(),
            'grades' => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'grade_number', 'name']),
            'terms' => Term::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']),
            'fee_structures' => FeeStructure::where('school_id', $school->id)
                ->with('grade:id,name,grade_number', 'term:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'amount', 'grade_id', 'term_id']),
            'filters' => compact('gradeId', 'termId', 'status'),
        ]);
    }

    public function store(RaiseInvoiceData $data): RedirectResponse
    {
        $school = app('current_school');
        $invoice = $this->financeService->raiseInvoice($school->id, $data);

        return redirect()->route('fee-invoices.show', $invoice)
            ->with('success', 'Invoice raised.');
    }

    public function show(FeeInvoice $feeInvoice): Response
    {
        $school = app('current_school');
        abort_if($feeInvoice->school_id !== $school->id, 403);

        $feeInvoice->load([
            'pupil:id,first_name,last_name,admission_no',
            'pupil.guardians:id,phone',
            'feeStructure:id,name,amount',
            'term:id,name',
            'payments.receivedBy:id,name',
        ]);

        // Pre-fill guardian phone for mobile money
        $guardianPhone = $feeInvoice->pupil?->guardians
            ->pluck('phone')
            ->filter()
            ->first();

        $gatewayManager = app(PaymentGatewayManager::class);

        return Inertia::render('Finance/Invoices/Show', [
            'invoice'        => $feeInvoice,
            'amount_paid'    => $feeInvoice->amount_paid,
            'outstanding'    => $feeInvoice->outstanding,
            'gateway_active' => $gatewayManager->active($school->id) !== null,
            'cash_enabled'   => $gatewayManager->cashEnabled($school->id),
            'guardian_phone' => $guardianPhone,
        ]);
    }

    public function bulkRaise(BulkRaiseInvoicesData $data): RedirectResponse
    {
        $school = app('current_school');
        $count = $this->financeService->bulkRaiseInvoices($school->id, $data);

        return back()->with('success', "{$count} invoices raised.");
    }
}
