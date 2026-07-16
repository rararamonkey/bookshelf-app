<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Book $book)
    {
        $alreadyReviewed = Review::where('user_id', auth()->id())
            ->where('book_id', $book->id)
            ->exists();

        if ($alreadyReviewed) {
            return redirect()->route('books.show', $book)
                ->with('error', 'この書籍にはすでにレビューを投稿しています。');
        }

        Review::create([
            'user_id' => auth()->id(),
            'book_id' => $book->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    public function edit(Review $review)
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    public function update(ReviewRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }

    public function like(Review $review)
    {
        $result = $review->likedByUsers()->toggle(auth()->id());

        if (! empty($result['attached'])) {
            return back()->with('success', 'いいねしました。');
        }

        return back()->with('success', 'いいねを解除しました。');
    }
}
