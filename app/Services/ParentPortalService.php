<?php

namespace App\Services;

use App\Data\CreatePortalAccountData;
use App\Data\SendParentMessageData;
use App\Mail\GuardianWelcomeMail;
use App\Models\AttendanceRecord;
use App\Models\Guardian;
use App\Models\GuardianPortalAccess;
use App\Models\ParentMessage;
use App\Models\PortalNotification;
use App\Models\Pupil;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ParentPortalService
{
    public function createPortalAccount(CreatePortalAccountData $data, string $temporaryPassword): User
    {
        $guardian = Guardian::findOrFail($data->guardian_id);

        $user = User::create([
            'name' => $guardian->full_name,
            'email' => $data->email,
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => now(),
            'is_parent' => true,
        ]);

        $user->assignRole('parent');

        GuardianPortalAccess::create([
            'guardian_id' => $guardian->id,
            'user_id' => $user->id,
            'activated_at' => now(),
        ]);

        // Update guardian user_id reference.
        $guardian->update(['user_id' => $user->id]);

        $loginUrl = route('login');
        Mail::to($data->email)->send(new GuardianWelcomeMail($user, $temporaryPassword, $loginUrl));

        return $user;
    }

    public function getChildrenForParent(int $userId): Collection
    {
        $portalAccess = GuardianPortalAccess::where('user_id', $userId)
            ->with('guardian.pupils.grade:id,name,grade_number')
            ->first();

        if (! $portalAccess) {
            return collect();
        }

        return $portalAccess->guardian->pupils ?? collect();
    }

    public function getChildSummary(int $userId, int $pupilId): array
    {
        $this->assertParentChildLink($userId, $pupilId);

        $pupil = Pupil::with([
            'grade:id,name,grade_number',
            'stream:id,name',
        ])->findOrFail($pupilId);

        $currentTerm = Term::where('school_id', $pupil->school_id)
            ->where('is_current', true)
            ->first();

        $attendanceSummary = null;
        if ($currentTerm) {
            $totalRecords = AttendanceRecord::whereHas(
                'attendanceSession',
                fn ($q) => $q->where('stream_id', $pupil->stream_id)
                    ->where('term_id', $currentTerm->id)
            )->where('pupil_id', $pupilId)->count();

            $presentCount = AttendanceRecord::whereHas(
                'attendanceSession',
                fn ($q) => $q->where('stream_id', $pupil->stream_id)
                    ->where('term_id', $currentTerm->id)
            )->where('pupil_id', $pupilId)
                ->where('status', 'present')
                ->count();

            $attendanceSummary = [
                'total' => $totalRecords,
                'present' => $presentCount,
                'percentage' => $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0,
            ];
        }

        $latestResults = TermResult::where('pupil_id', $pupilId)
            ->published()
            ->with('subject:id,name,code')
            ->orderByDesc('id')
            ->get(['id', 'subject_id', 'total_marks', 'grade_letter', 'term_id']);

        return [
            'pupil' => $pupil,
            'current_term' => $currentTerm,
            'attendance_summary' => $attendanceSummary,
            'latest_results' => $latestResults,
            'fee_balance' => null, // Module 10+
            'notices' => [],   // Module 12+
        ];
    }

    public function sendMessage(SendParentMessageData $data, int $senderId): ParentMessage
    {
        $sender = User::findOrFail($senderId);

        return ParentMessage::create([
            'school_id' => $sender->school_id,
            'sender_id' => $senderId,
            'recipient_id' => $data->recipient_id,
            'pupil_id' => $data->pupil_id,
            'message' => $data->message,
        ]);
    }

    public function getMessages(int $userId): Collection
    {
        return ParentMessage::where('sender_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->with([
                'sender:id,name',
                'recipient:id,name',
                'pupil:id,first_name,last_name',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn ($msg) => $msg->sender_id === $userId
                ? $msg->recipient_id
                : $msg->sender_id
            );
    }

    public function markNotificationRead(int $notifId, int $userId): void
    {
        PortalNotification::where('id', $notifId)
            ->where('user_id', $userId)
            ->update(['read_at' => now()]);
    }

    public function notifyParent(int $guardianUserId, string $title, string $message, string $type, ?Model $related = null): void
    {
        $user = User::find($guardianUserId);
        if (! $user) {
            return;
        }

        PortalNotification::create([
            'user_id' => $guardianUserId,
            'school_id' => $user->school_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->getKey(),
        ]);
    }

    private function assertParentChildLink(int $userId, int $pupilId): void
    {
        $portalAccess = GuardianPortalAccess::where('user_id', $userId)
            ->with('guardian.pupils')
            ->firstOrFail();

        $isLinked = $portalAccess->guardian->pupils
            ->contains('id', $pupilId);

        abort_if(! $isLinked, 403, 'You do not have access to this pupil\'s data.');
    }
}
