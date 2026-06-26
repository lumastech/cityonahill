<?php

namespace App\Http\Controllers;

use App\Data\IssueBorrowingData;
use App\Data\ReturnBookData;
use App\Models\BookBorrowing;
use App\Models\LibraryBook;
use App\Models\Pupil;
use App\Models\Staff;
use App\Services\LibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BorrowingController extends Controller
{
    public function __construct(private readonly LibraryService $libraryService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $borrowings = BookBorrowing::where('school_id', $school->id)
            ->with('book:id,title,author', 'issuedBy:id,name')
            ->orderByDesc('borrowed_date')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Library/Borrow/Index', [
            'borrowings' => $borrowings,
        ]);
    }

    public function create(): Response
    {
        $school = app('current_school');

        return Inertia::render('Library/Borrow/Create', [
            'available_books' => LibraryBook::where('school_id', $school->id)
                ->available()
                ->orderBy('title')
                ->get(['id', 'title', 'author', 'copies_available']),
        ]);
    }

    public function searchBorrower(Request $request): \Illuminate\Http\JsonResponse
    {
        $school = app('current_school');
        $type = $request->string('type')->toString();
        $q = $request->string('q')->trim()->toString();

        if ($type === 'staff') {
            $results = Staff::where('school_id', $school->id)
                ->active()
                ->with('user:id,name')
                ->whereHas('user', fn ($query) => $query->where('name', 'like', "%{$q}%"))
                ->orWhere(fn ($query) => $query->where('school_id', $school->id)->where('employee_no', 'like', "%{$q}%"))
                ->limit(15)
                ->get(['id', 'user_id', 'employee_no', 'position'])
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'label' => $s->user?->name . ' — ' . $s->employee_no,
                ]);
        } else {
            $results = Pupil::where('school_id', $school->id)
                ->where('status', 'active')
                ->where(fn ($query) => $query
                    ->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('admission_no', 'like', "%{$q}%"))
                ->orderBy('last_name')
                ->limit(15)
                ->get(['id', 'first_name', 'last_name', 'admission_no'])
                ->map(fn ($p) => [
                    'id'    => $p->id,
                    'label' => "{$p->first_name} {$p->last_name} ({$p->admission_no})",
                ]);
        }

        return response()->json($results);
    }

    public function store(IssueBorrowingData $data): RedirectResponse
    {
        $school = app('current_school');
        $borrowing = $this->libraryService->issueBook($school->id, $data, auth()->id());

        return redirect()->route('borrowings.index')
            ->with('success', 'Book issued successfully.');
    }

    public function update(ReturnBookData $data, BookBorrowing $borrowing): RedirectResponse
    {
        abort_if($borrowing->school_id !== app('current_school')?->id, 403);

        $this->libraryService->returnBook($data, auth()->id());

        return back()->with('success', 'Book returned.');
    }
}
