<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_own_reading_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertViewHas('stats');
    }

    public function test_guest_cannot_view_reading_report(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_report_displays_total_review_count(): void
    {
        $user = User::factory()->create();

        Review::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['summary']['total_reviews'] === 3;
        });
    }

    public function test_report_displays_completed_reading_plan_count(): void
{
    $user = User::factory()->create();

    ReadingPlan::factory()->count(2)->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Completed,
        'completed_at' => now(),
    ]);

    ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Reading,
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('reports.index'));

    $response->assertViewHas('stats', function (array $stats): bool {
        return $stats['summary']['books_read'] === 2;
    });
}

    public function test_report_displays_average_rating(): void
    {
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function (array $stats): bool {
            return (float) $stats['summary']['average_rating'] === 4.0;
        });
    }

    public function test_report_displays_rating_distribution(): void
    {
        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function (array $stats): bool {
            $distribution = $stats['rating_distribution'];

            return (int) ($distribution[5] ?? 0) === 2
                && (int) ($distribution[3] ?? 0) === 1
                && (int) ($distribution[1] ?? 0) === 0;
        });
    }

    public function test_report_uses_only_logged_in_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Review::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        Review::factory()->count(3)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['summary']['total_reviews'] === 2;
        });
    }

    public function test_top_rated_books_only_include_ratings_of_four_or_more_and_are_limited_to_five(): void
    {
        $user = User::factory()->create();

        Review::factory()->count(6)->create([
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function (array $stats): bool {
            $topRatedBooks = $stats['top_rated_books'];

            return $topRatedBooks->count() === 5
                && $topRatedBooks->every(
                    fn (array $book): bool => $book['rating'] >= 4
                );
        });
    }

    public function test_genre_rating_trends_are_limited_to_five(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 6; $i++) {
            $genre = Genre::factory()->create([
                'name' => "ジャンル{$i}",
            ]);

            $book = Book::factory()->create();

            $book->genres()->attach($genre->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['genre_ratings']->count() === 5;
        });
    }

    public function test_empty_report_returns_zero_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['summary']['total_reviews'] === 0
                && $stats['summary']['books_read'] === 0
                && (float) $stats['summary']['average_rating'] === 0.0
                && $stats['top_rated_books']->isEmpty()
                && $stats['genre_ratings']->isEmpty();
        });
    }
    public function test_report_counts_only_own_completed_reading_plans(): void
{
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // 自分の読了済み：2冊
    ReadingPlan::factory()->count(2)->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Completed,
        'completed_at' => now(),
    ]);

    // 他人の読了済み：3冊
    ReadingPlan::factory()->count(3)->create([
        'user_id' => $otherUser->id,
        'status' => ReadingPlanStatus::Completed,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('reports.index'));

    $response->assertOk();

    $response->assertViewHas('stats', function (array $stats): bool {
        return $stats['summary']['books_read'] === 2;
    });
}
}