<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_favorite_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/favorite");

        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }
    public function test_user_can_unfavorite_book(): void
{
    $user = User::factory()->create();

    $book = Book::factory()->create();

    // 1回目：お気に入り登録
    $this->actingAs($user)
        ->post("/books/{$book->id}/favorite");

    // 2回目：お気に入り解除
    $response = $this->actingAs($user)
        ->post("/books/{$book->id}/favorite");

    $response->assertRedirect();

    $this->assertDatabaseMissing('favorites', [
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);
}
public function test_user_can_view_own_favorites(): void
{
    $user = User::factory()->create();
    $other = User::factory()->create();

    $book1 = Book::factory()->create([
        'title' => '自分のお気に入り',
    ]);

    $book2 = Book::factory()->create([
        'title' => '他人のお気に入り',
    ]);

    $user->favoriteBooks()->attach($book1->id);
    $other->favoriteBooks()->attach($book2->id);

    $response = $this->actingAs($user)
        ->get('/favorites');

    $response->assertStatus(200);
    $response->assertSee('自分のお気に入り');
    $response->assertDontSee('他人のお気に入り');
}
}