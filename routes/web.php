<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books', [BookController::class, 'index']);

Route::get('/ranking', function () {
    return view('ranking.index');
})->name('ranking.index');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Book
    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');

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
    Route::get('/favorites', function () {
        return view('favorites.index');
    })->name('favorites.index');

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