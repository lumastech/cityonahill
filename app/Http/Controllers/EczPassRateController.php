<?php

namespace App\Http\Controllers;

use App\Services\EczService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EczPassRateController extends Controller
{
    public function __construct(private readonly EczService $eczService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');
        $gradeLevel = $request->integer('grade_level') ?: 12;
        $examYear = $request->integer('exam_year') ?: now()->year;

        $analytics = $this->eczService->getSchoolPassRate($school->id, $gradeLevel, $examYear);

        return Inertia::render('ECZ/Analytics', [
            'analytics' => $analytics,
            'grade_level' => $gradeLevel,
            'exam_year' => $examYear,
        ]);
    }
}
