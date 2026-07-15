<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceivablesController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');
        $asOf = $request->date('as_of')?->toDateString();

        return Inertia::render('Finance/Receivables/Index', [
            'aging' => $this->financeService->getReceivablesAging($school->id, $asOf),
            'filters' => ['as_of' => $asOf],
        ]);
    }
}
