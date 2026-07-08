<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Http\Resources\Api\V1\BookDetailResource;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    public function index()
{
    $query = Book::with(['genres', 'reviews']);

    if (request()->filled('keyword')) {
        $keyword = request('keyword');

        $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', '%' . $keyword . '%')
                ->orWhere('author', 'like', '%' . $keyword . '%');
        });
    }

    if (request()->filled('genre')) {
        $genreId = request('genre');

        $query->whereHas('genres', function ($q) use ($genreId) {
            $q->where('genres.id', $genreId);
        });
    }

    $sort = request('sort', 'latest');

match ($sort) {
    'oldest' => $query->oldest(),
    'title' => $query->orderBy('title'),
    'rating' => $query->withAvg('reviews', 'rating')
        ->orderByDesc('reviews_avg_rating'),
    default => $query->latest(),
};

$books = $query->paginate(10)
    ->withQueryString();

    $genres = Genre::all();

    return view('books.index', compact('books', 'genres'));
}

    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        $alreadyReviewed = false;

        if (auth()->check()) {
            $alreadyReviewed = $book->reviews()
                ->where('user_id', auth()->id())
                ->exists();
        }

        return view('books.show', compact('book', 'alreadyReviewed'));
    }

    public function create()
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

    public function edit(Book $book)
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
    public function searchByIsbn(string $isbn)
{
    if (! preg_match('/^\d{13}$/', $isbn)) {
        return response()->json([
            'message' => 'ISBNは13桁で入力してください。',
        ], 422);
    }

    $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
    'q' => 'isbn:' . $isbn,
    'key' => config('services.google_books.key'),
]);

    if ($response->failed()) {
        return response()->json([
            'message' => '書籍情報の取得に失敗しました。',
        ], 500);
    }

    $data = $response->json();

    if (empty($data['items'])) {
        return response()->json([
            'message' => '該当する書籍が見つかりませんでした。',
        ], 404);
    }

    $volumeInfo = $data['items'][0]['volumeInfo'];

    return response()->json([
        'isbn' => $isbn,
        'title' => $volumeInfo['title'] ?? '',
        'author' => isset($volumeInfo['authors'])
            ? implode('、', $volumeInfo['authors'])
            : '',
        'published_date' => $volumeInfo['publishedDate'] ?? null,
        'description' => $volumeInfo['description'] ?? '',
        'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
    ]);
}
}