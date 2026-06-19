<?php

namespace App\Http\Controllers;

use App\Models\PortalNotification;
use App\Services\ParentPortalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortalNotificationController extends Controller
{
    public function __construct(private readonly ParentPortalService $portalService) {}

    public function index(): Response
    {
        $notifications = PortalNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(30);

        return Inertia::render('Parent/Notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(int $id): RedirectResponse
    {
        $this->portalService->markNotificationRead($id, auth()->id());

        return back();
    }
}
