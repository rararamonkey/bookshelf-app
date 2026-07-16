<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    public function test_book_index_can_be_filtered_by_genre_id(): void
    {
        $programmingGenre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programmingBook = Book::factory()->create([
            'title' => 'Laravel実践',
        ]);

        $novelBook = Book::factory()->create([
            'title' => 'テスト小説',
        ]);

        $programmingBook->genres()->attach($programmingGenre->id);
        $novelBook->genres()->attach($novelGenre->id);

        $response = $this->getJson(
            "/api/v1/books?genre_id={$programmingGenre->id}"
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Laravel実践',
            ])
            ->assertJsonMissing([
                'title' => 'テスト小説',
            ]);
    }

    public function test_book_index_returns_pagination_information(): void
    {
        Book::factory()->count(16)->create();

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $response->assertJsonPath('meta.total', 16);
    }

    public function test_book_detail_returns_genres_reviews_and_review_user_name(): void
    {
        $user = User::factory()->create([
            'name' => 'レビュー投稿者',
        ]);

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'title' => 'API詳細確認書籍',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'APIの詳細テストです。',
        ]);

        $response = $this->getJson(
            "/api/v1/books/{$book->id}"
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'API詳細確認書籍',
            ])
            ->assertJsonFragment([
                'name' => '技術書',
            ])
            ->assertJsonFragment([
                'comment' => 'APIの詳細テストです。',
            ])
            ->assertJsonFragment([
                'user_name' => 'レビュー投稿者',
            ]);
    }

    public function test_api_book_store_returns_422_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'genres',
            ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_api_book_store_allows_null_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => '任意項目なしのAPI書籍',
            'author' => 'API著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => '任意項目なしのAPI書籍',
            'isbn' => null,
            'published_date' => null,
        ]);
    }

    public function test_api_book_update_returns_422_when_isbn_is_used_by_another_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'isbn' => '9781111111111',
        ]);

        Book::factory()->create([
            'isbn' => '9782222222222',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新対象書籍',
            'author' => '更新対象著者',
            'isbn' => '9782222222222',
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isbn');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_api_book_update_can_keep_its_own_isbn(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'isbn' => '9783333333333',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'ISBNを維持した書籍',
            'author' => '更新著者',
            'isbn' => '9783333333333',
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'ISBNを維持した書籍',
            'isbn' => '9783333333333',
        ]);
    }

    public function test_api_book_index_returns_422_for_invalid_search_parameters(): void
    {
        $response = $this->getJson(
            '/api/v1/books?genre_id=999999&page=0&per_page=101'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id',
                'page',
                'per_page',
            ]);
    }
}
