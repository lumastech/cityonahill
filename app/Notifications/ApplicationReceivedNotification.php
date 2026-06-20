<?php

namespace App\Notifications;

use App\Models\SchoolApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReceivedNotification extends Notification
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
            ->subject('New School Application — ' . $this->application->school_name)
            ->greeting('Hello,')
            ->line('A new school application has been submitted and is awaiting your review.')
            ->line('**School:** ' . $this->application->school_name)
            ->line('**Subdomain:** ' . $this->application->subdomain)
            ->line('**Applicant:** ' . $this->application->applicant->name)
            ->action('Review Application', route('admin.applications.show', $this->application))
            ->line('Please log in to review, approve, or request more information.');
    }
}
