<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index_displays_ten_books_per_page(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->get('/books');

        $response->assertOk();
        $response->assertViewHas('books', function ($books): bool {
            return $books->count() === 10
                && $books->total() === 11
                && $books->lastPage() === 2;
        });
    }

    public function test_books_index_displays_each_books_genres(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->get('/books');

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertSee('プログラミング');
    }

    public function test_book_detail_displays_book_genre_and_review(): void
    {
        $reviewer = User::factory()->create([
            'name' => 'レビュー投稿者',
        ]);

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'title' => '詳細確認書籍',
            'author' => '詳細確認著者',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても分かりやすいです。',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('詳細確認書籍');
        $response->assertSee('詳細確認著者');
        $response->assertSee('技術書');
        $response->assertSee('とても分かりやすいです。');
        $response->assertSee('レビュー投稿者');
    }

    public function test_genre_index_displays_book_count(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'PHP',
        ]);

        $books = Book::factory()->count(2)->create();

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('PHP');
        $response->assertSee('2');
    }

    public function test_genre_detail_displays_only_linked_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $linkedBook = Book::factory()->create([
            'title' => '紐づく書籍',
        ]);

        Book::factory()->create([
            'title' => '紐づかない書籍',
        ]);

        $linkedBook->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('紐づく書籍');
        $response->assertDontSee('紐づかない書籍');
    }

    public function test_duplicate_genre_name_cannot_be_registered(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '小説',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertSame(
            1,
            Genre::where('name', '小説')->count()
        );
    }

    public function test_same_user_cannot_review_same_book_twice(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '1回目のレビュー',
        ]);

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => '2回目のレビュー',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'error',
            'この書籍にはすでにレビューを投稿しています。'
        );

        $this->assertSame(
            1,
            Review::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->count()
        );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '1回目のレビュー',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '2回目のレビュー',
        ]);
    }
}
