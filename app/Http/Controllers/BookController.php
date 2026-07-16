<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $books = Book::query()
            ->with(['genres', 'user'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->keyword.'%')
                        ->orWhere('author', 'like', '%'.$request->keyword.'%');
                });
            })
            ->when($request->filled('genre'), function ($query) use ($request) {
                $query->whereHas('genres', function ($q) use ($request) {
                    $q->where('genres.id', $request->genre);
                });
            })
            ->when($request->sort === 'rating', fn ($q) => $q->orderByDesc('reviews_avg_rating'))
            ->when($request->sort === 'title', fn ($q) => $q->orderBy('title'))
            ->when($request->sort === 'oldest', fn ($q) => $q->oldest())
            ->when(! in_array($request->sort, ['rating', 'title', 'oldest'], true), fn ($q) => $q->latest())
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::orderBy('name')->get();

        return view('books.index', compact('books', 'genres'));
    }

    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        $alreadyReviewed = auth()->check()
            ? $book->reviews()->where('user_id', auth()->id())->exists()
            : false;

        return view('books.show', compact('book', 'alreadyReviewed'));
    }

    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(BookStoreRequest $request)
    {
        $book = Book::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'published_date' => $request->published_date,
            'description' => $request->description,
            'image_url' => $request->image_url,
        ]);

        $book->genres()->sync($request->genres);

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(BookUpdateRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $book->update([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'published_date' => $request->published_date,
            'description' => $request->description,
            'image_url' => $request->image_url,
        ]);

        $book->genres()->sync($request->genres);

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました。');
    }

    public function fetchByIsbn(string $isbn)
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 422);
        }

        $response = Http::get(
            "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}"
        );

        if (! $response->successful()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 500);
        }

        $item = collect($response->json('items', []))->first();

        if (! $item) {
            return response()->json([
                'error' => '書籍情報が見つかりませんでした。',
            ], 404);
        }

        $info = $item['volumeInfo'] ?? [];

        return response()->json([
            'title' => $info['title'] ?? '',
            'author' => collect($info['authors'] ?? [])->join('、'),
            'published_date' => $info['publishedDate'] ?? '',
            'description' => $info['description'] ?? '',
            'image_url' => $info['imageLinks']['thumbnail'] ?? '',
        ]);
    }
}
