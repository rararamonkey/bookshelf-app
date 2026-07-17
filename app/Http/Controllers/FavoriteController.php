<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧を表示する。
     */
    public function index(): View
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->latest()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * お気に入りを登録または解除する。
     */
    public function toggle(Book $book): RedirectResponse
    {
        $result = $book->favoritedUsers()->toggle(auth()->id());

        if (! empty($result['attached'])) {
            return back()->with('success', 'お気に入りに追加しました。');
        }

        return back()->with('success', 'お気に入りを解除しました。');
    }
}
