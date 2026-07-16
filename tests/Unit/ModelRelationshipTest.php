<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_books(): void
    {
        $user = User::factory()->create();

        Book::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $user->books);
        $this->assertInstanceOf(Book::class, $user->books->first());
    }

    public function test_book_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($book->user->is($user));
    }

    public function test_user_has_many_reviews(): void
    {
        $user = User::factory()->create();

        Review::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $user->reviews);
        $this->assertInstanceOf(Review::class, $user->reviews->first());
    }

    public function test_review_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($review->user->is($user));
    }

    public function test_book_has_many_reviews(): void
    {
        $book = Book::factory()->create();

        Review::factory()->count(2)->create([
            'book_id' => $book->id,
        ]);

        $this->assertCount(2, $book->reviews);
        $this->assertInstanceOf(Review::class, $book->reviews->first());
    }

    public function test_review_belongs_to_book(): void
    {
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $this->assertTrue($review->book->is($book));
    }

    public function test_book_belongs_to_many_genres(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $book->genres()->attach($genres->pluck('id'));

        $this->assertCount(2, $book->fresh()->genres);
    }

    public function test_genre_belongs_to_many_books(): void
    {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(2)->create();

        $genre->books()->attach($books->pluck('id'));

        $this->assertCount(2, $genre->fresh()->books);
    }

    public function test_user_has_favorite_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create();

        DB::table('favorites')->insert([
            [
                'user_id' => $user->id,
                'book_id' => $books[0]->id,
            ],
            [
                'user_id' => $user->id,
                'book_id' => $books[1]->id,
            ],
        ]);

        $this->assertCount(2, $user->fresh()->favoriteBooks);
    }

    public function test_review_has_users_who_liked_it(): void
    {
        $review = Review::factory()->create();
        $users = User::factory()->count(2)->create();

        DB::table('review_likes')->insert([
            [
                'user_id' => $users[0]->id,
                'review_id' => $review->id,
            ],
            [
                'user_id' => $users[1]->id,
                'review_id' => $review->id,
            ],
        ]);

        $this->assertCount(2, $review->fresh()->likedByUsers);
    }
}
