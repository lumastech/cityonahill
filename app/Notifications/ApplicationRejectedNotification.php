<?php

namespace App\Notifications;

use App\Models\SchoolApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationRejectedNotification extends Notification
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
        $mail = (new MailMessage)
            ->subject('Your School Application Was Not Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('After careful review, your application for **' . $this->application->school_name . '** has not been approved.');

        if ($this->application->reviewer_notes) {
            $mail->line('**Reason:** ' . $this->application->reviewer_notes);
        }

        return $mail->line('If you believe this decision was made in error, please contact us for further assistance.');
    }
}
