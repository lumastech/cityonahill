<?php

use App\Data\IssueBorrowingData;
use App\Data\ReturnBookData;
use App\Models\BookBorrowing;
use App\Models\LibraryBook;
use App\Models\Pupil;
use App\Models\School;
use App\Models\User;
use App\Services\LibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::factory()->create(['code' => 'LIB']);
    $this->service = app(LibraryService::class);
    $this->user = User::factory()->create();

    $this->book = LibraryBook::create([
        'school_id' => $this->school->id,
        'title' => 'Introduction to PHP',
        'author' => 'John Doe',
        'category' => 'Technology',
        'copies_total' => 3,
        'copies_available' => 3,
    ]);
});

it('issuing a book decrements available copies', function () {
    $pupil = Pupil::factory()->create(['school_id' => $this->school->id]);

    $data = IssueBorrowingData::from([
        'book_id' => $this->book->id,
        'borrower_type' => 'pupil',
        'borrower_id' => $pupil->id,
        'due_date' => now()->addDays(14)->toDateString(),
    ]);

    $borrowing = $this->service->issueBook($this->school->id, $data, $this->user->id);

    $this->book->refresh();

    expect($borrowing->status)->toBe('borrowed')
        ->and($this->book->copies_available)->toBe(2);
});

it('returning a book increments available copies', function () {
    $pupil = Pupil::factory()->create(['school_id' => $this->school->id]);

    $issueData = IssueBorrowingData::from([
        'book_id' => $this->book->id,
        'borrower_type' => 'pupil',
        'borrower_id' => $pupil->id,
        'due_date' => now()->addDays(14)->toDateString(),
    ]);

    $borrowing = $this->service->issueBook($this->school->id, $issueData, $this->user->id);

    $this->book->refresh();
    expect($this->book->copies_available)->toBe(2);

    $returnData = ReturnBookData::from([
        'borrowing_id' => $borrowing->id,
        'returned_date' => now()->toDateString(),
    ]);

    $this->service->returnBook($returnData, $this->user->id);

    $this->book->refresh();

    expect($this->book->copies_available)->toBe(3);
});

it('overdue fine calculated correctly', function () {
    $pupil = Pupil::factory()->create(['school_id' => $this->school->id]);

    $dueDate = now()->subDays(5)->toDateString();

    $borrowing = BookBorrowing::create([
        'school_id' => $this->school->id,
        'book_id' => $this->book->id,
        'borrower_type' => 'pupil',
        'borrower_id' => $pupil->id,
        'borrowed_date' => now()->subDays(19)->toDateString(),
        'due_date' => $dueDate,
        'status' => 'borrowed',
        'issued_by' => $this->user->id,
    ]);

    $returnData = ReturnBookData::from([
        'borrowing_id' => $borrowing->id,
        'returned_date' => now()->toDateString(),
    ]);

    $returned = $this->service->returnBook($returnData, $this->user->id);

    // 5 days overdue × ZMW 0.50 default rate = ZMW 2.50
    expect((float) $returned->fine_amount)->toBe(2.50);
});

it('book cannot be issued if copies_available is 0', function () {
    $this->book->update(['copies_available' => 0]);

    $pupil = Pupil::factory()->create(['school_id' => $this->school->id]);

    $data = IssueBorrowingData::from([
        'book_id' => $this->book->id,
        'borrower_type' => 'pupil',
        'borrower_id' => $pupil->id,
        'due_date' => now()->addDays(14)->toDateString(),
    ]);

    expect(fn () => $this->service->issueBook($this->school->id, $data, $this->user->id))
        ->toThrow(HttpException::class);
});
