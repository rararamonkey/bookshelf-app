<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する。
     */
    public function index(Request $request): View
    {
        $keyword = $request->string('keyword')->toString();
        $genreId = $request->input('genre');
        $sort = $request->input('sort');

        $books = Book::query()
            ->with(['genres', 'user'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->when(
                $keyword !== '',
                fn (Builder $bookQuery): Builder => $this->applyKeywordFilter(
                    $bookQuery,
                    $keyword
                )
            )
            ->when(
                $genreId,
                fn (Builder $bookQuery): Builder => $this->applyGenreFilter(
                    $bookQuery,
                    $genreId
                )
            );

        $this->applySort($books, $sort);

        $books = $books
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍詳細を表示する。
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        $alreadyReviewed = auth()->check()
            && $book->reviews()
                ->where('user_id', auth()->id())
                ->exists();

        return view('books.show', compact('book', 'alreadyReviewed'));
    }

    /**
     * 書籍登録画面を表示する。
     */
    public function create(): View
    {
        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録する。
     */
    public function store(BookStoreRequest $request): RedirectResponse
    {
        $book = DB::transaction(function () use ($request): Book {
            $book = Book::create([
                'user_id' => $request->user()->id,
                'title' => $request->string('title')->toString(),
                'author' => $request->string('author')->toString(),
                'isbn' => $request->input('isbn'),
                'published_date' => $request->input('published_date'),
                'description' => $request->input('description'),
                'image_url' => $request->input('image_url'),
            ]);

            $book->genres()->sync($request->input('genres', []));

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍編集画面を表示する。
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍を更新する。
     */
    public function update(
        BookUpdateRequest $request,
        Book $book
    ): RedirectResponse {
        $this->authorize('update', $book);

        DB::transaction(function () use ($request, $book): void {
            $book->update([
                'title' => $request->string('title')->toString(),
                'author' => $request->string('author')->toString(),
                'isbn' => $request->input('isbn'),
                'published_date' => $request->input('published_date'),
                'description' => $request->input('description'),
                'image_url' => $request->input('image_url'),
            ]);

            $book->genres()->sync($request->input('genres', []));
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍を削除する。
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }

    /**
     * ISBNから書籍情報を取得する。
     */
    public function fetchByIsbn(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 422);
        }

        $response = Http::timeout(10)->get(
            "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}"
        );

        if (! $response->successful()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 500);
        }

        $item = collect($response->json('items', []))->first();

        if ($item === null) {
            return response()->json([
                'error' => '書籍情報が見つかりませんでした。',
            ], 404);
        }

        $bookInformation = $item['volumeInfo'] ?? [];

        return response()->json([
            'title' => $bookInformation['title'] ?? '',
            'author' => collect(
                $bookInformation['authors'] ?? []
            )->join('、'),
            'published_date' => $bookInformation['publishedDate'] ?? '',
            'description' => $bookInformation['description'] ?? '',
            'image_url' => $bookInformation['imageLinks']['thumbnail'] ?? '',
        ]);
    }

    /**
     * タイトルまたは著者名で絞り込む。
     */
    private function applyKeywordFilter(
        Builder $bookQuery,
        string $keyword
    ): Builder {
        return $bookQuery->where(
            function (Builder $keywordQuery) use ($keyword): void {
                $keywordQuery
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            }
        );
    }

    /**
     * ジャンルで絞り込む。
     */
    private function applyGenreFilter(
        Builder $bookQuery,
        mixed $genreId
    ): Builder {
        return $bookQuery->whereHas(
            'genres',
            function (Builder $genreQuery) use ($genreId): void {
                $genreQuery->where('genres.id', $genreId);
            }
        );
    }

    /**
     * 指定された条件で書籍を並び替える。
     */
    private function applySort(
        Builder $bookQuery,
        ?string $sort
    ): void {
        match ($sort) {
            'rating' => $bookQuery->orderByDesc('reviews_avg_rating'),
            'title' => $bookQuery->orderBy('title'),
            'oldest' => $bookQuery->oldest(),
            default => $bookQuery->latest(),
        };
    }
}
