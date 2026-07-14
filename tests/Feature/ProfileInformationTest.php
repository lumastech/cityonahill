<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('self-service profile information updates are disabled', function () {
    expect(Features::enabled(Features::updateProfileInformation()))->toBeFalse();
});

test('users cannot change their own name or email', function () {
    $user = User::factory()->create([
        'name'  => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->actingAs($user)
        ->put('/user/profile-information', [
            'name'  => 'Hacked Name',
            'email' => 'hacked@example.com',
        ])
        ->assertNotFound();

    expect($user->fresh())
        ->name->toEqual('Original Name')
        ->email->toEqual('original@example.com');
});
