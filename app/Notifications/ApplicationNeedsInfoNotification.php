<?php

namespace App\Notifications;

use App\Models\SchoolApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationNeedsInfoNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly SchoolApplication $application) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your School Application Needs More Information')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your application for **' . $this->application->school_name . '** requires some additional information before it can be processed.')
            ->line('**Reviewer notes:**')
            ->line($this->application->reviewer_notes ?? 'Please update your application with any missing details.')
            ->action('Update Application', route('onboarding.show', $this->application))
            ->line('Please log in, review the notes above, update your application, and re-submit.');
    }
}
