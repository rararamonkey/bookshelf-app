<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index_returns_200_and_resource_data(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'PHP',
        ]);

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => 'テスト著者',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'genres',
                        'average_rating',
                        'reviews_count',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonFragment([
                'title' => 'Laravel入門',
                'author' => 'テスト著者',
                'reviews_count' => 1,
            ]);
    }

    public function test_books_can_be_searched_by_title_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP基礎',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk();
        $response->assertJsonFragment([
            'title' => 'Laravel入門',
        ]);
        $response->assertJsonMissing([
            'title' => 'PHP基礎',
        ]);
    }

    public function test_books_can_be_searched_by_author_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP基礎',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=佐藤');

        $response->assertOk();
        $response->assertJsonFragment([
            'author' => '佐藤花子',
        ]);
        $response->assertJsonMissing([
            'author' => '山田太郎',
        ]);
    }

    public function test_books_can_be_filtered_by_genre_id(): void
    {
        $phpGenre = Genre::factory()->create([
            'name' => 'PHP',
        ]);

        $designGenre = Genre::factory()->create([
            'name' => 'デザイン',
        ]);

        $phpBook = Book::factory()->create([
            'title' => 'PHPの本',
        ]);

        $designBook = Book::factory()->create([
            'title' => 'デザインの本',
        ]);

        $phpBook->genres()->attach($phpGenre->id);
        $designBook->genres()->attach($designGenre->id);

        $response = $this->getJson(
            "/api/v1/books?genre_id={$phpGenre->id}"
        );

        $response->assertOk();
        $response->assertJsonFragment([
            'title' => 'PHPの本',
        ]);
        $response->assertJsonMissing([
            'title' => 'デザインの本',
        ]);
    }

    public function test_per_page_controls_number_of_books(): void
    {
        Book::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/books?per_page=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_per_page_over_100_returns_422(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=101');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_book_detail_returns_200_and_expected_data(): void
    {
        $user = User::factory()->create([
            'name' => 'レビュー投稿者',
        ]);

        $genre = Genre::factory()->create([
            'name' => 'Laravel',
        ]);

        $book = Book::factory()->create([
            'title' => 'Laravel実践',
            'author' => 'テスト著者',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '分かりやすい本です。',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'genres',
                    'reviews',
                    'average_rating',
                    'reviews_count',
                ],
            ])
            ->assertJsonFragment([
                'title' => 'Laravel実践',
                'comment' => '分かりやすい本です。',
            ]);
    }

    public function test_nonexistent_book_detail_returns_404(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();
    }
}