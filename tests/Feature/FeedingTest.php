<?php

use App\Data\AddStockData;
use App\Data\OpenFeedingSessionData;
use App\Data\RecordFeedingData;
use App\Models\FeedingSession;
use App\Models\Pupil;
use App\Models\School;
use App\Models\User;
use App\Services\FeedingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'FED']);
    $this->service = app(FeedingService::class);
    $this->user = User::factory()->create();
});

it('feeding session records served pupils', function () {
    $pupils = Pupil::factory()->count(5)->create(['school_id' => $this->school->id]);

    $sessionData = OpenFeedingSessionData::from([
        'date' => now()->toDateString(),
        'meal_type' => 'lunch',
    ]);

    $session = $this->service->openSession($this->school->id, $sessionData, $this->user->id);

    expect($session)->toBeInstanceOf(FeedingSession::class);

    $servedPupilIds = $pupils->take(3)->pluck('id')->toArray();

    $recordData = RecordFeedingData::from([
        'session_id' => $session->id,
        'pupil_ids' => $servedPupilIds,
    ]);

    $count = $this->service->recordFeeding($recordData);

    expect($count)->toBe(3)
        ->and($session->fresh()->served_count)->toBe(3);
});

it('low stock alert triggered at reorder level', function () {
    $data = AddStockData::from([
        'item_name' => 'Maize Meal',
        'unit' => 'kg',
        'quantity' => 10.0,
        'reorder_level' => 10.0,
    ]);

    $stock = $this->service->addStock($this->school->id, $data);

    // Exactly at reorder level — should be in low stock alerts
    $alerts = $this->service->getLowStockAlerts($this->school->id);

    expect($alerts)->toHaveCount(1)
        ->and($alerts->first()->item_name)->toBe('Maize Meal');

    // Add another item well above reorder — should NOT appear
    $safeData = AddStockData::from([
        'item_name' => 'Sugar',
        'unit' => 'kg',
        'quantity' => 50.0,
        'reorder_level' => 5.0,
    ]);

    $this->service->addStock($this->school->id, $safeData);

    $alerts = $this->service->getLowStockAlerts($this->school->id);

    expect($alerts)->toHaveCount(1)
        ->and($alerts->first()->item_name)->toBe('Maize Meal');
});
