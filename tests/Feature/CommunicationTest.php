<?php

use App\Data\CreateNoticeData;
use App\Data\SendMessageData;
use App\Data\SendSmsData;
use App\Jobs\SendSmsJob;
use App\Models\Notice;
use App\Models\School;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'COM']);
    $this->service = app(CommunicationService::class);
    $this->user = User::factory()->create();

    app()->instance('current_school', $this->school);
});

it('notice published and visible to correct audience', function () {
    $notice = $this->service->createNotice($this->school->id, CreateNoticeData::from([
        'title' => 'Sports Day',
        'content' => 'Annual sports day is next Friday.',
        'target_audience' => 'parents',
        'published_at' => now()->toDateTimeString(),
    ]), $this->user->id);

    expect($notice->title)->toBe('Sports Day');
    expect($notice->target_audience)->toBe('parents');

    $visible = Notice::where('school_id', $this->school->id)
        ->active()
        ->forAudience('parents')
        ->get();

    expect($visible)->toHaveCount(1);
    expect($visible->first()->id)->toBe($notice->id);
});

it('parent cannot see staff-only notices', function () {
    $this->service->createNotice($this->school->id, CreateNoticeData::from([
        'title' => 'Staff Meeting',
        'content' => 'Mandatory staff meeting on Monday.',
        'target_audience' => 'staff',
        'published_at' => now()->toDateTimeString(),
    ]), $this->user->id);

    $visibleToParents = Notice::where('school_id', $this->school->id)
        ->active()
        ->forAudience('parents')
        ->get();

    expect($visibleToParents)->toHaveCount(0);
});

it('SMS job queued on bulk send', function () {
    Queue::fake();

    $data = SendSmsData::from([
        'phones' => ['0977000001', '0977000002', '0977000003'],
        'message' => 'School closes early today.',
        'provider' => 'airtel',
    ]);

    $logs = $this->service->sendSms($this->school->id, $data);

    Queue::assertPushed(SendSmsJob::class, 3);
    expect($logs)->toHaveCount(3);
    expect(SmsLog::where('school_id', $this->school->id)->count())->toBe(3);
});

it('SMS log status updated on delivery or failure', function () {
    $log = SmsLog::create([
        'school_id' => $this->school->id,
        'recipient_phone' => '0977000001',
        'message' => 'Test message',
        'status' => 'pending',
        'provider' => 'airtel',
    ]);

    $log->update(['status' => 'sent', 'sent_at' => now()]);

    expect($log->fresh()->status)->toBe('sent');
    expect($log->fresh()->sent_at)->not->toBeNull();

    $log->update(['status' => 'failed']);
    expect($log->fresh()->status)->toBe('failed');
});

it('parent can message class teacher', function () {
    $teacher = User::factory()->create();

    $data = SendMessageData::from([
        'recipient_id' => $teacher->id,
        'message' => 'My child was sick today.',
    ]);

    $message = $this->service->sendMessage($data, $this->user->id);

    expect($message->sender_id)->toBe($this->user->id);
    expect($message->recipient_id)->toBe($teacher->id);
    expect($message->message)->toBe('My child was sick today.');

    $thread = $this->service->getThread($this->user->id, $teacher->id);
    expect($thread)->toHaveCount(1);
});
