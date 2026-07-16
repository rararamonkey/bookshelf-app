<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'message' => 'ログインしました。',
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_returns_401_with_incorrect_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
    }

    public function test_login_returns_422_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
            ]);
    }

    public function test_unauthenticated_user_cannot_create_book(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'genres' => [$genre->id],
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('books', [
            'title' => 'テスト書籍',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_book(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_authenticated_user_is_saved_as_book_owner(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'API登録書籍',
            'author' => 'API登録著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'API登録書籍',
            'author' => 'API登録著者',
        ]);
    }

    public function test_owner_can_update_own_book_through_api(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);
        $genre = Genre::factory()->create();

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'API更新後タイトル',
            'author' => 'API更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'API更新後タイトル',
            'author' => 'API更新後著者',
        ]);
    }

    public function test_other_user_cannot_update_book_through_api(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $genre = Genre::factory()->create();

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '不正な更新タイトル',
            'author' => '不正な更新著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正な更新タイトル',
        ]);
    }

    public function test_owner_can_delete_own_book_through_api(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_delete_book_through_api(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
