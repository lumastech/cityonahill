<?php

namespace App\Http\Controllers;

use App\Data\SendParentMessageData;
use App\Services\ParentPortalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ParentMessageController extends Controller
{
    public function __construct(private readonly ParentPortalService $portalService) {}

    public function index(): Response
    {
        $threads = $this->portalService->getMessages(auth()->id());

        return Inertia::render('Parent/Messages', [
            'threads' => $threads,
        ]);
    }

    public function __invoke(SendParentMessageData $data): RedirectResponse
    {
        $this->portalService->sendMessage($data, auth()->id());

        return back()->with('success', 'Message sent.');
    }
}
