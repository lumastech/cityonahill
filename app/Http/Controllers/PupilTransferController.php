<?php

namespace App\Http\Controllers;

use App\Data\TransferPupilData;
use App\Models\Pupil;
use App\Services\PupilService;
use Illuminate\Http\RedirectResponse;

class PupilTransferController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function __invoke(TransferPupilData $data, Pupil $pupil): RedirectResponse
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $this->pupilService->transfer($pupil->id, $data, auth()->id());

        $message = $data->type === 'external'
            ? "Pupil transferred to {$data->to_school}."
            : 'Pupil moved to new stream.';

        return redirect()->route('pupils.show', $pupil)->with('success', $message);
    }
}
