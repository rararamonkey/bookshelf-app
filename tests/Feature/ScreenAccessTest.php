<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_book_index(): void
    {
        $this->get(route('books.index'))
            ->assertOk();
    }

    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk();
    }

    public function test_guest_can_view_ranking(): void
    {
        $this->get(route('ranking.index'))
            ->assertOk();
    }

    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.create'))
            ->assertOk();
    }

    public function test_guest_cannot_view_book_create_page(): void
    {
        $this->get(route('books.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_genre_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk();
    }

    public function test_guest_cannot_view_genre_index(): void
    {
        $this->get(route('genres.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_favorites_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk();
    }

    public function test_guest_cannot_view_favorites_index(): void
    {
        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_reading_plan_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reading-plans.index'))
            ->assertOk();
    }

    public function test_guest_cannot_view_reading_plan_index(): void
    {
        $this->get(route('reading-plans.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_notifications_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();
    }

    public function test_guest_cannot_view_notifications_index(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_report_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_guest_cannot_view_report_page(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_book_edit_page(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('books.edit', $book))
            ->assertOk();
    }

    public function test_other_user_cannot_view_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($otherUser)
            ->get(route('books.edit', $book))
            ->assertForbidden();
    }

    public function test_review_author_can_view_review_edit_page(): void
    {
        $author = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $this->actingAs($author)
            ->get(route('reviews.edit', $review))
            ->assertOk();
    }

    public function test_other_user_cannot_view_review_edit_page(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $this->actingAs($otherUser)
            ->get(route('reviews.edit', $review))
            ->assertForbidden();
    }

    public function test_authenticated_user_can_view_genre_detail(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk();
    }
}
