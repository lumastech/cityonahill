<?php

namespace App\Http\Controllers;

use App\Data\AddBookData;
use App\Models\LibraryBook;
use App\Services\LibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryBookController extends Controller
{
    public function __construct(private readonly LibraryService $libraryService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');
        $search = $request->string('search')->toString() ?: null;
        $category = $request->string('category')->toString() ?: null;

        $books = $search
            ? $this->libraryService->searchBooks($school->id, $search)
            : LibraryBook::where('school_id', $school->id)
                ->when($category, fn ($q) => $q->byCategory($category))
                ->with('media')
                ->orderBy('title')
                ->paginate(24)
                ->withQueryString();

        $categories = LibraryBook::where('school_id', $school->id)
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Library/Books/Index', [
            'books' => $books,
            'categories' => $categories,
            'filters' => compact('search', 'category'),
        ]);
    }

    public function store(AddBookData $data, Request $request): RedirectResponse
    {
        $school = app('current_school');
        $book = $this->libraryService->addBook($school->id, $data);

        if ($request->hasFile('cover')) {
            $book->addMedia($request->file('cover'))->toMediaCollection('book-cover');
        }

        return redirect()->route('library-books.show', $book)
            ->with('success', 'Book added to catalogue.');
    }

    public function show(LibraryBook $libraryBook): Response
    {
        abort_if($libraryBook->school_id !== app('current_school')?->id, 403);

        $libraryBook->load([
            'subject:id,name',
            'borrowings' => fn ($q) => $q->orderByDesc('borrowed_date')->limit(20),
            'borrowings.issuedBy:id,name',
        ]);

        $libraryBook->loadMedia('book-cover');

        $categories = LibraryBook::where('school_id', $libraryBook->school_id)
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Library/Books/Show', [
            'book'       => $libraryBook,
            'cover_url'  => $libraryBook->getFirstMediaUrl('book-cover'),
            'categories' => $categories,
        ]);
    }

    public function update(AddBookData $data, Request $request, LibraryBook $libraryBook): RedirectResponse
    {
        abort_if($libraryBook->school_id !== app('current_school')?->id, 403);

        // Adjust copies_available proportionally when total changes
        $delta = $data->copies_total - $libraryBook->copies_total;
        $newAvailable = max(0, $libraryBook->copies_available + $delta);

        $libraryBook->update([
            'title'          => $data->title,
            'author'         => $data->author,
            'isbn'           => $data->isbn,
            'publisher'      => $data->publisher,
            'publish_year'   => $data->publish_year,
            'category'       => $data->category,
            'subject_id'     => $data->subject_id,
            'copies_total'   => $data->copies_total,
            'copies_available' => $newAvailable,
            'shelf_location' => $data->shelf_location,
            'description'    => $data->description,
        ]);

        if ($request->hasFile('cover')) {
            $libraryBook->clearMediaCollection('book-cover');
            $libraryBook->addMedia($request->file('cover'))->toMediaCollection('book-cover');
        }

        return redirect()->route('library-books.show', $libraryBook)
            ->with('success', 'Book updated.');
    }

    public function destroy(LibraryBook $libraryBook): RedirectResponse
    {
        abort_if($libraryBook->school_id !== app('current_school')?->id, 403);

        $libraryBook->delete();

        return redirect()->route('library-books.index')
            ->with('success', 'Book removed from catalogue.');
    }
}
