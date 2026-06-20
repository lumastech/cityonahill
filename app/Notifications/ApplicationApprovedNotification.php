<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\SchoolApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SchoolApplication $application,
        public readonly School $school,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appUrl    = config('app.url');
        $scheme    = parse_url($appUrl, PHP_URL_SCHEME) ?? 'https';
        $host      = parse_url($appUrl, PHP_URL_HOST) ?? $appUrl;
        $schoolUrl = $scheme . '://' . $this->school->subdomain . '.' . $host;

        return (new MailMessage)
            ->subject('Your School Is Live — ' . $this->school->name)
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('Your application for **' . $this->school->name . '** has been approved and your school portal is now active.')
            ->line('**Your school URL:** ' . $schoolUrl)
            ->action('Go to School Portal', $schoolUrl)
            ->line('Log in with your existing credentials to start configuring your school.');
    }
}
