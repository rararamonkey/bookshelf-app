<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_books_index(): void
    {
        $response = $this->get('/books');

        $response->assertStatus(200);
    }

    public function test_books_index_displays_book_title(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $response = $this->get('/books');

        $response->assertStatus(200);
        $response->assertSee('Laravel入門');
    }

    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::factory()->create([
            'title' => 'Laravel詳細テスト',
        ]);

        $response = $this->get("/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertSee('Laravel詳細テスト');
    }

    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/create');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_book_create_page(): void
    {
        $response = $this->get('/books/create');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'Laravel実践',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'published_date' => '2024-01-01',
            'description' => 'テスト用書籍',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [Genre::factory()->create()->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'Laravel実践',
        ]);
    }

    public function test_book_cannot_be_created_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', []);

        $response
            ->assertSessionHasErrors([
                'title',
                'author',
                'genres',
            ]);
    }

    public function test_owner_can_view_book_edit_page(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/books/{$book->id}/edit");

        $response->assertStatus(200);
    }

    public function test_other_user_cannot_view_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)
            ->get("/books/{$book->id}/edit");

        $response->assertForbidden();
    }

    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2024-02-01',
            'description' => '更新後の説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);
    }

    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)
            ->put("/books/{$book->id}", [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => $book->isbn,
                'published_date' => '2024-02-01',
                'description' => '更新後説明',
                'image_url' => 'https://example.com/update.jpg',
                'genres' => [$genre->id],
            ]);

        $response->assertForbidden();
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)
            ->delete("/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_book_can_be_created_without_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
        ]);
    }
}
