<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Services\CommunicationService;
use Illuminate\Http\RedirectResponse;

class PublishNoticeController extends Controller
{
    public function __construct(private readonly CommunicationService $service) {}

    public function __invoke(Notice $notice): RedirectResponse
    {
        $this->service->publishNotice($notice->id);

        return redirect()->route('notices.index')
            ->with('success', 'Notice published.');
    }
}
