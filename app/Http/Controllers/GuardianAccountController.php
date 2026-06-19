<?php

namespace App\Http\Controllers;

use App\Data\CreatePortalAccountData;
use App\Mail\GuardianWelcomeMail;
use App\Models\Guardian;
use App\Services\ParentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GuardianAccountController extends Controller
{
    public function __construct(private readonly ParentPortalService $portalService) {}

    public function store(CreatePortalAccountData $data): RedirectResponse
    {
        $temporaryPassword = Str::password(12);

        $this->portalService->createPortalAccount($data, $temporaryPassword);

        return back()->with('success', 'Portal account created and welcome email sent.');
    }

    public function resendCredentials(Guardian $guardian): RedirectResponse
    {
        $portalAccess = $guardian->load('portalAccess.user')->portalAccess;
        abort_if(! $portalAccess, 404, 'No portal account exists for this guardian.');

        $temporaryPassword = Str::password(12);
        $user = $portalAccess->user;

        $user->update(['password' => Hash::make($temporaryPassword)]);

        Mail::to($user->email)->send(
            new GuardianWelcomeMail($user, $temporaryPassword, route('login'))
        );

        return back()->with('success', 'New credentials sent to '.$user->email);
    }
}
