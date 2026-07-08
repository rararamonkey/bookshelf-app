<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Genre;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalReviews = $user->reviews()->count();

        $completedBooks = $user->reviews()
            ->distinct('book_id')
            ->count('book_id');

        $averageRating = round(
            $user->reviews()->avg('rating') ?? 0,
            1
        );

        $ratingDistribution = [];

        for ($i = 5; $i >= 1; $i--) {
        $ratingDistribution[$i] = $user->reviews()
            ->where('rating', $i)
            ->count();

        $topBooks = $user->reviews()
            ->with('book')
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->latest()
            ->take(5)
            ->get();

        $genreRatings = Genre::query()
            ->join('book_genre', 'genres.id', '=', 'book_genre.genre_id')
            ->join('books', 'book_genre.book_id', '=', 'books.id')
            ->join('reviews', 'books.id', '=', 'reviews.book_id')
            ->where('reviews.user_id', $user->id)
            ->select(
        'genres.id',
        'genres.name',
        DB::raw('AVG(reviews.rating) as average_rating'),
        DB::raw('COUNT(reviews.id) as reviews_count')
    )
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('average_rating')
            ->limit(5)
            ->get();
}

        return view('reports.index', compact(
            'totalReviews',
            'completedBooks',
            'averageRating',
            'ratingDistribution',
            'topBooks',
            'genreRatings'
        ));
    }
}