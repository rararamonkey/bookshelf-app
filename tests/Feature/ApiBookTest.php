<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧を取得できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $book->genres()->attach($genre->id);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'published_date',
                        'description',
                        'image_url',
                        'genres',
                        'average_rating',
                        'reviews_count',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_書籍詳細を取得できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $book->genres()->attach($genre->id);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $book->id);
    }

    public function test_未認証では書籍登録できない(): void
    {
        $response = $this->postJson('/api/v1/books', []);

        $response->assertUnauthorized();
    }

    public function test_認証済みなら書籍登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-07-06',
            'description' => 'テスト説明',
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', '書籍を登録しました。');

        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'isbn' => '9781234567890',
        ]);
    }

    public function test_認証済みなら書籍更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-07-07',
            'description' => '更新後説明',
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', '書籍を更新しました。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);
    }

    public function test_認証済みなら書籍削除できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_バリデーションエラー時は422を返す(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_id',
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);
    }
}
