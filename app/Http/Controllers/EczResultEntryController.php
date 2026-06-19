<?php

namespace App\Http\Controllers;

use App\Data\EnterActualResultData;
use App\Services\EczService;
use Illuminate\Http\RedirectResponse;

class EczResultEntryController extends Controller
{
    public function __construct(private readonly EczService $eczService) {}

    public function __invoke(EnterActualResultData $data): RedirectResponse
    {
        $this->eczService->enterActualResults($data->results);

        return back()->with('success', 'ECZ results entered successfully.');
    }
}
