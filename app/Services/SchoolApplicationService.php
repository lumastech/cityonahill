<?php

namespace App\Services;

use App\Enums\ApplicationStatusEnum;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolApplication;
use App\Models\SchoolApplicationLog;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\ApplicationApprovedNotification;
use App\Notifications\ApplicationNeedsInfoNotification;
use App\Notifications\ApplicationReceivedNotification;
use App\Notifications\ApplicationRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class SchoolApplicationService
{
    public function submit(SchoolApplication $application, User $actor): void
    {
        $action = $application->exists ? 'resubmitted' : 'submitted';

        $application->fill([
            'status'       => ApplicationStatusEnum::Pending,
            'submitted_at' => now(),
        ])->save();

        SchoolApplicationLog::create([
            'application_id' => $application->id,
            'actor_id'       => $actor->id,
            'action'         => $action,
        ]);

        // Notify all super-admins
        $superAdmins = User::role('super-admin')->get();
        Notification::send($superAdmins, new ApplicationReceivedNotification($application));
    }

    public function needsInfo(SchoolApplication $application, User $reviewer, ?string $notes): void
    {
        $application->update([
            'status'         => ApplicationStatusEnum::NeedsInfo,
            'reviewer_id'    => $reviewer->id,
            'reviewer_notes' => $notes,
            'reviewed_at'    => now(),
        ]);

        SchoolApplicationLog::create([
            'application_id' => $application->id,
            'actor_id'       => $reviewer->id,
            'action'         => 'needs_info',
            'notes'          => $notes,
        ]);

        $application->applicant->notify(new ApplicationNeedsInfoNotification($application));
    }

    public function reject(SchoolApplication $application, User $reviewer, ?string $notes): void
    {
        $application->update([
            'status'         => ApplicationStatusEnum::Rejected,
            'reviewer_id'    => $reviewer->id,
            'reviewer_notes' => $notes,
            'reviewed_at'    => now(),
        ]);

        SchoolApplicationLog::create([
            'application_id' => $application->id,
            'actor_id'       => $reviewer->id,
            'action'         => 'rejected',
            'notes'          => $notes,
        ]);

        $application->applicant->notify(new ApplicationRejectedNotification($application));
    }

    public function approve(SchoolApplication $application, User $reviewer): School
    {
        return DB::transaction(function () use ($application, $reviewer): School {
            // Create the school record
            $school = School::create([
                'name'               => $application->school_name,
                'subdomain'          => $application->subdomain,
                'type'               => $application->type,
                'level'              => $application->level,
                'province'           => $application->province,
                'district'           => $application->district,
                'address'            => $application->address,
                'phone'              => $application->contact_phone,
                'email'              => $application->contact_email,
                'moe_registration_no' => $application->moe_registration_no,
                'owner_id'           => $application->applicant_id,
                'status'             => 'active',
            ]);

            // Assign applicant as school owner/headteacher
            $applicant = $application->applicant;
            $applicant->update(['school_id' => $school->id]);

            // Assign headteacher role (create if it doesn't exist)
            $role = Role::firstOrCreate(['name' => 'headteacher', 'guard_name' => 'web']);
            $applicant->assignRole($role);

            // Enable requested modules via school settings
            if ($application->modules_config) {
                foreach ($application->modules_config as $module) {
                    $school->settings()->create([
                        'key'   => "module.{$module}",
                        'value' => '1',
                    ]);
                }
            }

            // Create subscription on the default plan
            $plan = Plan::where('is_active', true)->orderBy('id')->first();
            if ($plan) {
                Subscription::create([
                    'user_id'         => $applicant->id,
                    'plan_id'         => $plan->id,
                    'status'          => 'active',
                    'school_count'    => 1,
                    'next_billing_at' => now()->addMonth(),
                ]);
            }

            // Mark application approved
            $application->update([
                'status'      => ApplicationStatusEnum::Approved,
                'reviewer_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            SchoolApplicationLog::create([
                'application_id' => $application->id,
                'actor_id'       => $reviewer->id,
                'action'         => 'approved',
            ]);

            $applicant->notify(new ApplicationApprovedNotification($application, $school));

            return $school;
        });
    }
}
