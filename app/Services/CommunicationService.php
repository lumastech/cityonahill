<?php

namespace App\Services;

use App\Data\CreateNoticeData;
use App\Data\SendMessageData;
use App\Data\SendSmsData;
use App\Jobs\SendSmsJob;
use App\Models\Notice;
use App\Models\Pupil;
use App\Models\SchoolMessage;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Collection;

class CommunicationService
{
    public function createNotice(int $schoolId, CreateNoticeData $data, int $createdBy): Notice
    {
        return Notice::create([
            'school_id' => $schoolId,
            'title' => $data->title,
            'content' => $data->content,
            'target_audience' => $data->target_audience,
            'target_grade_id' => $data->target_grade_id,
            'published_at' => $data->published_at,
            'expires_at' => $data->expires_at,
            'created_by' => $createdBy,
        ]);
    }

    public function publishNotice(int $noticeId): Notice
    {
        $notice = Notice::findOrFail($noticeId);
        $notice->update(['published_at' => now()]);

        return $notice->fresh();
    }

    public function getNoticesForUser(int $userId, int $schoolId): Collection
    {
        $user = User::with('roles')->findOrFail($userId);
        $roles = $user->roles->pluck('name');

        $audience = match (true) {
            $roles->contains('parent') => 'parents',
            $roles->contains('teacher') || $roles->contains('staff') => 'staff',
            $roles->contains('pupil') => 'pupils',
            default => 'all',
        };

        return Notice::where('school_id', $schoolId)
            ->active()
            ->forAudience($audience)
            ->orderByDesc('published_at')
            ->get();
    }

    public function sendSms(int $schoolId, SendSmsData $data): Collection
    {
        $logs = collect();

        foreach ($data->phones as $phone) {
            $log = SmsLog::create([
                'school_id' => $schoolId,
                'recipient_phone' => $phone,
                'message' => $data->message,
                'status' => 'pending',
                'provider' => $data->provider,
            ]);

            dispatch(new SendSmsJob(
                phone: $phone,
                message: $data->message,
                provider: $data->provider,
                schoolId: $schoolId,
                smsLogId: $log->id,
            ));

            $logs->push($log);
        }

        return $logs;
    }

    public function sendBulkSmsToGradeParents(int $schoolId, int $gradeId, string $message): int
    {
        $phones = Pupil::where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereHas('stream', fn ($q) => $q->where('grade_id', $gradeId))
            ->with('guardians:id,phone')
            ->get()
            ->flatMap(fn ($pupil) => $pupil->guardians->pluck('phone'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($phones)) {
            return 0;
        }

        $data = SendSmsData::from(['phones' => $phones, 'message' => $message]);
        $this->sendSms($schoolId, $data);

        return count($phones);
    }

    public function sendMessage(SendMessageData $data, int $senderId): SchoolMessage
    {
        return SchoolMessage::create([
            'school_id' => app('current_school')->id,
            'sender_id' => $senderId,
            'recipient_id' => $data->recipient_id,
            'pupil_id' => $data->pupil_id,
            'message' => $data->message,
        ]);
    }

    public function getThread(int $userA, int $userB): Collection
    {
        return SchoolMessage::thread($userA, $userB)
            ->with(['sender:id,name', 'recipient:id,name'])
            ->orderBy('created_at')
            ->get();
    }
}
