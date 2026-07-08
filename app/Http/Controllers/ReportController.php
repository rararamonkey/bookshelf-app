<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * ログインユーザーの読書レポートを表示する。
     */
    public function index(): View
    {
        $reviews = Review::with('book.genres')
            ->whereBelongsTo(auth()->user())
            ->get();

        $ratingDistribution = collect(range(1, 5))
            ->mapWithKeys(fn (int $rating): array => [
                $rating => $reviews->where('rating', $rating)->count(),
            ]);

        $topRatedBooks = $reviews
            ->filter(fn (Review $review): bool => $review->rating >= 4)
            ->sortByDesc('rating')
            ->take(5)
            ->map(fn (Review $review): array => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values();

        $genreRatings = $reviews
            ->flatMap(fn (Review $review): Collection => $review->book->genres->map(fn ($genre): array => [
                'id' => $genre->id,
                'name' => $genre->name,
                'rating' => $review->rating,
            ]))
            ->groupBy('id')
            ->map(fn (Collection $items): array => [
                'id' => $items->first()['id'],
                'name' => $items->first()['name'],
                'count' => $items->count(),
                'average_rating' => round($items->avg('rating'), 1),
            ])
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews->pluck('book_id')->unique()->count(),
                'average_rating' => round($reviews->avg('rating') ?? 0, 1),
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}