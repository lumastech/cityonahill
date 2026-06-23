<?php

namespace App\Services;

use App\Data\AddBookData;
use App\Data\IssueBorrowingData;
use App\Data\ReturnBookData;
use App\Models\BookBorrowing;
use App\Models\LibraryBook;
use App\Models\SchoolSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LibraryService
{
    public function addBook(int $schoolId, AddBookData $data): LibraryBook
    {
        return LibraryBook::create([
            'school_id' => $schoolId,
            'title' => $data->title,
            'author' => $data->author,
            'isbn' => $data->isbn,
            'publisher' => $data->publisher,
            'publish_year' => $data->publish_year,
            'category' => $data->category,
            'subject_id' => $data->subject_id,
            'copies_total' => $data->copies_total,
            'copies_available' => $data->copies_total,
            'shelf_location' => $data->shelf_location,
            'description' => $data->description,
        ]);
    }

    public function issueBook(int $schoolId, IssueBorrowingData $data, int $issuedBy): BookBorrowing
    {
        $book = LibraryBook::findOrFail($data->book_id);

        if ($book->copies_available < 1) {
            abort(422, 'No copies available for this book.');
        }

        $book->decrement('copies_available');

        return BookBorrowing::create([
            'school_id' => $schoolId,
            'book_id' => $data->book_id,
            'borrower_type' => $data->borrower_type,
            'borrower_id' => $data->borrower_id,
            'borrowed_date' => now()->toDateString(),
            'due_date' => $data->due_date,
            'status' => 'borrowed',
            'issued_by' => $issuedBy,
        ]);
    }

    public function returnBook(ReturnBookData $data, int $returnedBy): BookBorrowing
    {
        $borrowing = BookBorrowing::with('book')->findOrFail($data->borrowing_id);

        $dueDate = Carbon::parse($borrowing->due_date);
        $returnedDate = Carbon::parse($data->returned_date);
        $daysOverdue = max(0, $dueDate->diffInDays($returnedDate, false));

        if ($daysOverdue > 0 && $returnedDate->gt($dueDate)) {
            $fineRate = (float) SchoolSetting::get($borrowing->school_id, 'library_fine_per_day', 0.50);
            $fineAmount = round($daysOverdue * $fineRate, 2);
        } else {
            $fineAmount = 0.0;
        }

        $borrowing->update([
            'returned_date' => $data->returned_date,
            'returned_to' => $returnedBy,
            'fine_amount' => $fineAmount,
            'status' => 'returned',
        ]);

        $borrowing->book->increment('copies_available');

        return $borrowing;
    }

    public function getOverdueBorrowings(int $schoolId): Collection
    {
        return BookBorrowing::where('school_id', $schoolId)
            ->overdue()
            ->with('book:id,title,author', 'issuedBy:id,name')
            ->orderBy('due_date')
            ->get();
    }

    public function getBorrowerHistory(string $borrowerType, int $borrowerId): Collection
    {
        return BookBorrowing::where('borrower_type', $borrowerType)
            ->where('borrower_id', $borrowerId)
            ->with('book:id,title,author,category')
            ->orderByDesc('borrowed_date')
            ->get();
    }

    public function searchBooks(int $schoolId, string $query): Collection
    {
        return LibraryBook::where('school_id', $schoolId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('author', 'like', "%{$query}%")
                    ->orWhere('isbn', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%");
            })
            ->with('media')
            ->orderBy('title')
            ->limit(30)
            ->get();
    }
}
