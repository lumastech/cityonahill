<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\Sms\SmsServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
        public readonly ?string $provider,
        public readonly int $schoolId,
        public readonly int $smsLogId,
    ) {}

    public function handle(): void
    {
        $log = SmsLog::find($this->smsLogId);

        try {
            $service = SmsServiceFactory::make($this->provider);
            $result = $service->send($this->phone, $this->message);

            $log?->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'external_message_id' => $result['external_message_id'] ?? null,
                'sent_at' => $result['success'] ? now() : null,
            ]);
        } catch (\Throwable $e) {
            $log?->update(['status' => 'failed']);
            throw $e;
        }
    }
}
