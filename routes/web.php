<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books', [BookController::class, 'index']);

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Book
    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::get('/books/isbn/{isbn}', [BookController::class, 'searchByIsbn'])
    ->name('books.isbn');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');
    
    Route::get('/reports', [ReportController::class, 'index'])
    ->name('reports.index');

    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');
    
    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
    ->name('reading-plans.create');

    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
    ->name('reading-plans.store');

    Route::patch('/reading-plans/{readingPlan}/status', [ReadingPlanController::class, 'updateStatus'])
    ->name('reading-plans.update-status');

    Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])
    ->name('reading-plans.edit');

    Route::put('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'update'])
    ->name('reading-plans.update');

    Route::delete('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'destroy'])
    ->name('reading-plans.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index');

Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
    ->name('notifications.read');
    

    /// Genre
Route::get('/genres', [GenreController::class, 'index'])
    ->name('genres.index');

Route::get('/genres/create', [GenreController::class, 'create'])
    ->name('genres.create');

Route::post('/genres', [GenreController::class, 'store'])
    ->name('genres.store');

Route::get('/genres/{genre}', [GenreController::class, 'show'])
    ->name('genres.show');

Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])
    ->name('genres.edit');

Route::put('/genres/{genre}', [GenreController::class, 'update'])
    ->name('genres.update');

Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])
    ->name('genres.destroy');

    // Favorite
    Route::get('/favorites', [FavoriteController::class, 'index'])
    ->name('favorites.index');

    Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    // Review
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->name('reviews.edit');

    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->name('reviews.update');

    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->name('reviews.destroy');

    Route::post('/reviews/{review}/like', [ReviewController::class, 'like'])
        ->name('reviews.like');
});

/*
|--------------------------------------------------------------------------
| Book Detail
|--------------------------------------------------------------------------
*/

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');