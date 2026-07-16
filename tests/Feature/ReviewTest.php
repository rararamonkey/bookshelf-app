<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/reviews", [
                'rating' => 5,
                'comment' => '最高でした',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '最高でした',
        ]);
    }

    public function test_review_cannot_be_created_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/reviews", []);

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);
    }

    public function test_owner_can_view_review_edit_page(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/reviews/{$review->id}/edit");

        $response->assertStatus(200);
    }

    public function test_other_user_cannot_view_review_edit_page(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($other)
            ->get("/reviews/{$review->id}/edit");

        $response->assertForbidden();
    }

    public function test_owner_can_update_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->put("/reviews/{$review->id}", [
                'rating' => 4,
                'comment' => '更新しました',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '更新しました',
        ]);
    }

    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($other)
            ->put("/reviews/{$review->id}", [
                'rating' => 4,
                'comment' => '更新しました',
            ]);

        $response->assertForbidden();
    }

    public function test_owner_can_delete_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/reviews/{$review->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($other)
            ->delete("/reviews/{$review->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}
