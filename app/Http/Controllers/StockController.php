<?php

namespace App\Http\Controllers;

use App\Data\AddStockData;
use App\Data\RecordStockMovementData;
use App\Models\FeedingStock;
use App\Services\FeedingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function __construct(private readonly FeedingService $feedingService) {}

    public function index(): Response
    {
        $school = app('current_school');

        $stocks = FeedingStock::where('school_id', $school->id)
            ->orderBy('item_name')
            ->get();

        $lowStockIds = $stocks->filter(fn ($s) => $s->quantity_on_hand <= $s->reorder_level)->pluck('id');

        return Inertia::render('Feeding/Stock/Index', [
            'stocks' => $stocks,
            'low_stock_ids' => $lowStockIds->values(),
        ]);
    }

    public function store(AddStockData $data): RedirectResponse
    {
        $school = app('current_school');
        $this->feedingService->addStock($school->id, $data);

        return back()->with('success', 'Stock item added.');
    }

    public function update(RecordStockMovementData $data, FeedingStock $feedingStock): RedirectResponse
    {
        abort_if($feedingStock->school_id !== app('current_school')?->id, 403);

        $this->feedingService->recordMovement($feedingStock->school_id, $data, auth()->id());

        return back()->with('success', 'Stock movement recorded.');
    }
}
