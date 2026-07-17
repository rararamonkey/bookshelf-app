<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * ログインユーザーの読書レポートを表示する。
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $reviews = $this->getUserReviews($user);

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $this->countCompletedBooks($user),
                'average_rating' => $this->calculateAverageRating($reviews),
            ],
            'rating_distribution' => $this->buildRatingDistribution($reviews),
            'top_rated_books' => $this->buildTopRatedBooks($reviews),
            'genre_ratings' => $this->buildGenreRatings($reviews),
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * ログインユーザーが投稿したレビューを取得する。
     *
     * @return Collection<int, Review>
     */
    private function getUserReviews(User $user): Collection
    {
        return Review::query()
            ->with('book.genres')
            ->whereBelongsTo($user)
            ->get();
    }

    /**
     * 読了済みの読書計画数を取得する。
     */
    private function countCompletedBooks(User $user): int
    {
        return ReadingPlan::query()
            ->whereBelongsTo($user)
            ->where('status', ReadingPlanStatus::Completed)
            ->count();
    }

    /**
     * レビューの平均評価を計算する。
     *
     * @param  Collection<int, Review>  $reviews
     */
    private function calculateAverageRating(Collection $reviews): float
    {
        return round($reviews->avg('rating') ?? 0, 1);
    }

    /**
     * 評価ごとのレビュー件数を集計する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, int>
     */
    private function buildRatingDistribution(
        Collection $reviews
    ): Collection {
        return collect(range(1, 5))
            ->mapWithKeys(
                fn (int $rating): array => [
                    $rating => $reviews
                        ->where('rating', $rating)
                        ->count(),
                ]
            );
    }

    /**
     * 評価4以上の書籍を最大5件取得する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array<string, int|string>>
     */
    private function buildTopRatedBooks(Collection $reviews): Collection
    {
        return $reviews
            ->filter(
                fn (Review $review): bool => $review->rating >= 4
            )
            ->sortByDesc('rating')
            ->take(5)
            ->map(
                fn (Review $review): array => [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ]
            )
            ->values();
    }

    /**
     * ジャンルごとのレビュー件数と平均評価を集計する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array<string, float|int|string>>
     */
    private function buildGenreRatings(Collection $reviews): Collection
    {
        return $reviews
            ->flatMap(
                fn (Review $review): Collection => $review->book->genres
                    ->map(
                        fn ($genre): array => [
                            'id' => $genre->id,
                            'name' => $genre->name,
                            'rating' => $review->rating,
                        ]
                    )
            )
            ->groupBy('id')
            ->map(
                fn (Collection $genreReviews): array => [
                    'id' => $genreReviews->first()['id'],
                    'name' => $genreReviews->first()['name'],
                    'count' => $genreReviews->count(),
                    'average_rating' => round(
                        $genreReviews->avg('rating'),
                        1
                    ),
                ]
            )
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();
    }
}
