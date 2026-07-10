<?php

namespace App\Services;

use App\Data\AddStockData;
use App\Data\OpenFeedingSessionData;
use App\Data\RecordFeedingData;
use App\Data\RecordStockMovementData;
use App\Models\FeedingRecord;
use App\Models\FeedingSession;
use App\Models\FeedingStock;
use App\Models\FeedingStockMovement;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeedingService
{
    public function openSession(int $schoolId, OpenFeedingSessionData $data, int $recordedBy): FeedingSession
    {
        return FeedingSession::firstOrCreate(
            [
                'school_id' => $schoolId,
                'date' => $data->date,
                'meal_type' => $data->meal_type,
                'stream_id' => $data->stream_id,
            ],
            ['recorded_by' => $recordedBy]
        );
    }

    public function recordFeeding(RecordFeedingData $data): int
    {
        $session = FeedingSession::findOrFail($data->session_id);

        abort_if($session->finalized, 422, 'Session is already finalized.');

        $upserted = 0;

        DB::transaction(function () use ($data, &$upserted) {
            foreach ($data->pupil_ids as $pupilId) {
                FeedingRecord::updateOrCreate(
                    ['session_id' => $data->session_id, 'pupil_id' => $pupilId],
                    ['served' => 1]
                );
                $upserted++;
            }
        });

        return $upserted;
    }

    public function finalizeSession(int $sessionId): FeedingSession
    {
        $session = FeedingSession::findOrFail($sessionId);
        $session->update(['finalized' => 1]);

        return $session;
    }

    public function getSchoolFeedingStats(int $schoolId, int $termId): array
    {
        $term = Term::find($termId);

        $query = FeedingRecord::whereHas('session', function ($q) use ($schoolId, $term) {
            $q->where('school_id', $schoolId);
            if ($term) {
                $q->whereBetween('date', [$term->start_date, $term->end_date]);
            }
        });

        $totalServed = $query->where('served', 1)->count();

        $byDay = FeedingSession::where('school_id', $schoolId)
            ->when($term, fn ($q) => $q->whereBetween('date', [$term->start_date, $term->end_date]))
            ->withCount(['feedingRecords as served_count' => fn ($q) => $q->where('served', 1)])
            ->get()
            ->groupBy(fn ($s) => $s->date->toDateString())
            ->map(fn ($sessions, $date) => ['date' => $date, 'meals' => $sessions->sum('served_count')])
            ->values();

        return [
            'total_served' => $totalServed,
            'by_day' => $byDay,
        ];
    }

    public function addStock(int $schoolId, AddStockData $data): FeedingStock
    {
        return FeedingStock::create([
            'school_id' => $schoolId,
            'item_name' => $data->item_name,
            'unit' => $data->unit,
            'quantity_on_hand' => $data->quantity,
            'reorder_level' => $data->reorder_level,
            'cost_per_unit' => $data->cost_per_unit,
            'last_restocked_at' => now()->toDateString(),
        ]);
    }

    public function recordMovement(int $schoolId, RecordStockMovementData $data, int $recordedBy): FeedingStockMovement
    {
        $stock = FeedingStock::findOrFail($data->stock_id);

        $movement = FeedingStockMovement::create([
            'school_id' => $schoolId,
            'stock_id' => $data->stock_id,
            'type' => $data->type,
            'quantity' => $data->quantity,
            'notes' => $data->notes,
            'recorded_by' => $recordedBy,
        ]);

        match ($data->type) {
            'restock' => $stock->increment('quantity_on_hand', $data->quantity),
            'consumption', 'wastage' => $stock->decrement('quantity_on_hand', $data->quantity),
            default => null,
        };

        if ($data->type === 'restock') {
            $stock->update(['last_restocked_at' => now()->toDateString()]);
        }

        return $movement;
    }

    public function getLowStockAlerts(int $schoolId): Collection
    {
        return FeedingStock::where('school_id', $schoolId)
            ->lowStock()
            ->orderBy('item_name')
            ->get();
    }
}
