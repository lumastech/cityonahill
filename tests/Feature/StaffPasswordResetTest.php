<?php

use App\Mail\StaffPasswordResetMail;
use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Mail::fake();

    $this->school = School::factory()->create(['code' => 'ZSS']);

    $this->staffUser = User::factory()->create([
        'school_id' => $this->school->id,
        'name'      => 'Grace Banda',
        'email'     => 'grace@example.com',
        'password'  => Hash::make('old-password'),
    ]);

    $this->staff = Staff::factory()->create([
        'school_id' => $this->school->id,
        'user_id'   => $this->staffUser->id,
        'status'    => 'active',
    ]);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create(['school_id' => test()->school->id]);
    $user->assignRole($role);

    return $user;
}

it('lets a headteacher reset a staff password, emails it, and shows it once', function () {
    $response = $this->actingAs(userWithRole('headteacher'))
        ->post(route('staff.reset-password', $this->staff->id));

    $response->assertRedirect()->assertSessionHas('generated_password');

    $generated = session('generated_password');

    // The password shown to the admin is the one that actually works.
    expect(Hash::check($generated, $this->staffUser->fresh()->password))->toBeTrue()
        ->and(Hash::check('old-password', $this->staffUser->fresh()->password))->toBeFalse();

    Mail::assertSent(StaffPasswordResetMail::class, function (StaffPasswordResetMail $mail) use ($generated) {
        return $mail->hasTo('grace@example.com')
            && $mail->temporaryPassword === $generated;
    });
});

it('forbids password reset for a deputy-headteacher', function () {
    $this->actingAs(userWithRole('deputy-headteacher'))
        ->post(route('staff.reset-password', $this->staff->id))
        ->assertForbidden();

    expect(Hash::check('old-password', $this->staffUser->fresh()->password))->toBeTrue();
    Mail::assertNothingSent();
});

it('lets an admin change a staff name and email', function () {
    $this->actingAs(userWithRole('school-admin'))
        ->put(route('staff.update', $this->staff->id), [
            'name'  => 'Grace M. Banda',
            'email' => 'grace.banda@example.com',
        ])
        ->assertRedirect();

    expect($this->staffUser->fresh())
        ->name->toEqual('Grace M. Banda')
        ->email->toEqual('grace.banda@example.com')
        // A new address must be re-verified.
        ->email_verified_at->toBeNull();
});

it('rejects an email already taken by another user', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs(userWithRole('school-admin'))
        ->put(route('staff.update', $this->staff->id), [
            'name'  => 'Grace Banda',
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect($this->staffUser->fresh()->email)->toEqual('grace@example.com');
});

it('forbids a deputy-headteacher from editing the staff record', function () {
    $this->actingAs(userWithRole('deputy-headteacher'))
        ->put(route('staff.update', $this->staff->id), ['basic_salary' => 99_999])
        ->assertForbidden();
});
