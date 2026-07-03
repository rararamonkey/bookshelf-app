<?php

namespace App\Http\Controllers;

use App\Models\Book;

class FavoriteController extends Controller
{
    // お気に入り一覧
    public function index()
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->latest()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    // お気に入り登録・解除
    public function toggle(Book $book)
    {
        $result = $book->favoritedUsers()->toggle(auth()->id());

        if (!empty($result['attached'])) {
            return back()->with('success', 'お気に入りに追加しました。');
        }

        return back()->with('success', 'お気に入りを解除しました。');
    }
}