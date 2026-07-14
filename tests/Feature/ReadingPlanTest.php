<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_start_planned_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Planned,
            'target_date' => today()->addWeek(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.start', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書を開始しました。');

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $readingPlan->status
        );
    }

    public function test_reading_plan_cannot_be_started_when_status_is_not_planned(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading,
            'target_date' => today()->addWeek(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.start', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $readingPlan->status
        );
    }

    public function test_user_cannot_start_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Planned,
            'target_date' => today()->addWeek(),
        ]);

        $response = $this->actingAs($otherUser)
            ->post(route('reading-plans.start', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Planned,
            $readingPlan->status
        );
    }
}