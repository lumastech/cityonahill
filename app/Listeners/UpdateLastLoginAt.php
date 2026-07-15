<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLoginAt
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof \App\Models\User) {
            // Avoid touching updated_at / firing model events for a login timestamp.
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }
}
