<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビューを投稿する。
     */
    public function store(
        ReviewRequest $request,
        Book $book
    ): RedirectResponse {
        $alreadyReviewed = Review::where('user_id', auth()->id())
            ->where('book_id', $book->id)
            ->exists();

        if ($alreadyReviewed) {
            return redirect()
                ->route('books.show', $book)
                ->with(
                    'error',
                    'この書籍にはすでにレビューを投稿しています。'
                );
        }

        Review::create([
            'user_id' => auth()->id(),
            'book_id' => $book->id,
            'rating' => $request->integer('rating'),
            'comment' => $request->input('comment'),
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    /**
     * レビュー編集画面を表示する。
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する。
     */
    public function update(
        ReviewRequest $request,
        Review $review
    ): RedirectResponse {
        $this->authorize('update', $review);

        $review->update([
            'rating' => $request->integer('rating'),
            'comment' => $request->input('comment'),
        ]);

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューを削除する。
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }

    /**
     * レビューへのいいねを登録または解除する。
     */
    public function like(Review $review): RedirectResponse
    {
        $result = $review->likedByUsers()->toggle(auth()->id());

        if (! empty($result['attached'])) {
            return back()->with('success', 'いいねしました。');
        }

        return back()->with('success', 'いいねを解除しました。');
    }
}
