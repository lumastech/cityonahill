<?php

namespace App\Http\Controllers;

use App\Services\PupilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PupilStatisticsController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $school = app('current_school');

        return response()->json(
            $this->pupilService->getSchoolStatistics($school->id)
        );
    }
}
