<?php

namespace App\Http\Controllers;

use App\Services\LibraryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverdueReportController extends Controller
{
    public function __construct(private readonly LibraryService $libraryService) {}

    public function __invoke(Request $request): Response
    {
        $school = app('current_school');
        $overdue = $this->libraryService->getOverdueBorrowings($school->id);

        return Inertia::render('Library/Overdue', [
            'overdue_borrowings' => $overdue,
        ]);
    }
}
