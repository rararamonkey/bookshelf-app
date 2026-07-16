<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApiBookIndexRequest;
use App\Http\Requests\Api\V1\ApiBookStoreRequest;
use App\Http\Requests\Api\V1\ApiBookUpdateRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BookController extends Controller
{
    public function index(ApiBookIndexRequest $request): AnonymousResourceCollection
    {
        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $query->where(function ($q) use ($request): void {
                    $q->where('title', 'like', '%'.$request->keyword.'%')
                        ->orWhere('author', 'like', '%'.$request->keyword.'%');
                });
            })
            ->when($request->filled('genre_id'), function ($query) use ($request): void {
                $query->whereHas('genres', function ($q) use ($request): void {
                    $q->where('genres.id', $request->genre_id);
                });
            })
            ->paginate((int) $request->input('per_page', 10));

        return BookResource::collection($books);
    }

    public function show(Book $book): BookDetailResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookDetailResource($book);
    }

    public function store(ApiBookStoreRequest $request): JsonResponse
    {
        $book = Book::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $book->genres()->sync($request->input('genres'));

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return (new BookResource($book))
            ->additional(['message' => '書籍を登録しました。'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(ApiBookUpdateRequest $request, Book $book): JsonResponse
    {
        $this->authorize('update', $book);

        $book->update($request->validated());
        $book->genres()->sync($request->input('genres'));

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return (new BookResource($book))
            ->additional(['message' => '書籍を更新しました。'])
            ->response();
    }

    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
