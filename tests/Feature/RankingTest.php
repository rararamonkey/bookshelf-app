<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_ranking_page(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
    }

    public function test_books_are_displayed_in_descending_order_of_average_rating(): void
    {
        $highRatedBook = Book::factory()->create([
            'title' => '評価5の書籍',
        ]);

        $middleRatedBook = Book::factory()->create([
            'title' => '評価4の書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '評価2の書籍',
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $middleRatedBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        $response = $this->get(route('ranking.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '評価5の書籍',
                '評価4の書籍',
                '評価2の書籍',
            ]);
    }

    public function test_average_rating_is_calculated_from_multiple_reviews(): void
    {
        $book = Book::factory()->create([
            'title' => '平均評価確認書籍',
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 3,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas(
            'rankedBooks',
            function ($books) use ($book): bool {
                $rankingBook = $books->firstWhere('id', $book->id);

                return $rankingBook !== null
                    && (float) $rankingBook->reviews_avg_rating === 4.0;
            }
        );
    }

    public function test_book_without_reviews_is_not_displayed(): void
    {
        $reviewedBook = Book::factory()->create([
            'title' => 'レビューあり書籍',
        ]);

        Book::factory()->create([
            'title' => 'レビューなし書籍',
        ]);

        Review::factory()->create([
            'book_id' => $reviewedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('レビューあり書籍');
        $response->assertDontSee('レビューなし書籍');
    }

    public function test_ranking_displays_a_maximum_of_ten_books(): void
    {
        $books = Book::factory()
            ->count(11)
            ->create();

        foreach ($books as $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas(
            'rankedBooks',
            function ($books): bool {
                return $books->count() === 10;
            }
        );
    }

    public function test_ranking_only_uses_reviews_for_each_book(): void
    {
        $firstBook = Book::factory()->create([
            'title' => '平均評価5の本',
        ]);

        $secondBook = Book::factory()->create([
            'title' => '平均評価3の本',
        ]);

        Review::factory()->create([
            'book_id' => $firstBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $secondBook->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'book_id' => $secondBook->id,
            'rating' => 4,
        ]);

        $response = $this->get(route('ranking.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '平均評価5の本',
                '平均評価3の本',
            ]);
    }
}
