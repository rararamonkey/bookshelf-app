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
    public function test_owner_can_update_target_date_of_reading_plan(): void
{
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $readingPlan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingPlanStatus::Reading,
        'target_date' => today()->addWeek(),
    ]);

    $newTargetDate = today()->addWeeks(2)->format('Y-m-d');

    $response = $this->actingAs($user)
        ->put(route('reading-plans.update', $readingPlan), [
            'target_date' => $newTargetDate,
        ]);

    $response->assertRedirect(route('reading-plans.index'));
    $response->assertSessionHas('success', '読書計画を更新しました。');

    $this->assertDatabaseHas('reading_plans', [
        'id' => $readingPlan->id,
        'status' => ReadingPlanStatus::Reading->value,
        'target_date' => $newTargetDate,
    ]);
}

public function test_updating_expired_reading_plan_returns_status_to_planned(): void
{
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $readingPlan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingPlanStatus::Expired,
        'target_date' => today()->subDay(),
    ]);

    $newTargetDate = today()->addWeek()->format('Y-m-d');

    $response = $this->actingAs($user)
        ->put(route('reading-plans.update', $readingPlan), [
            'target_date' => $newTargetDate,
        ]);

    $response->assertRedirect(route('reading-plans.index'));
    $response->assertSessionHas('success', '読書計画を更新しました。');

    $this->assertDatabaseHas('reading_plans', [
        'id' => $readingPlan->id,
        'status' => ReadingPlanStatus::Planned->value,
        'target_date' => $newTargetDate,
    ]);
}

public function test_owner_can_complete_expired_reading_plan(): void
{
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $readingPlan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingPlanStatus::Expired,
        'target_date' => today()->subDay(),
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('reading-plans.complete', $readingPlan));

    $response->assertRedirect(route('reading-plans.index'));
    $response->assertSessionHas('success', '読書計画を読了にしました。');

    $readingPlan->refresh();

    $this->assertSame(
        ReadingPlanStatus::Completed,
        $readingPlan->status
    );

    $this->assertNotNull($readingPlan->completed_at);
}

public function test_completed_reading_plan_cannot_be_updated(): void
{
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $readingPlan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingPlanStatus::Completed,
        'target_date' => today()->addWeek(),
        'completed_at' => now(),
    ]);

    $originalTargetDate = $readingPlan->target_date->format('Y-m-d');

    $response = $this->actingAs($user)
        ->put(route('reading-plans.update', $readingPlan), [
            'target_date' => today()->addMonth()->format('Y-m-d'),
        ]);

    $response->assertForbidden();

    $readingPlan->refresh();

    $this->assertSame(
        $originalTargetDate,
        $readingPlan->target_date->format('Y-m-d')
    );

    $this->assertSame(
        ReadingPlanStatus::Completed,
        $readingPlan->status
    );
}

public function test_same_user_cannot_create_duplicate_reading_plan_for_same_book(): void
{
    $user = User::factory()->create();
    $book = Book::factory()->create();

    ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingPlanStatus::Planned,
        'target_date' => today()->addWeek(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => today()->addWeeks(2)->format('Y-m-d'),
        ]);

    $response->assertSessionHasErrors('book_id');

    $this->assertSame(
        1,
        ReadingPlan::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->count()
    );
}

public function test_user_can_create_same_reading_plan_again_after_deleting_existing_plan(): void
{
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $readingPlan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingPlanStatus::Planned,
        'target_date' => today()->addWeek(),
    ]);

    $deleteResponse = $this->actingAs($user)
        ->delete(route('reading-plans.destroy', $readingPlan));

    $deleteResponse->assertRedirect(route('reading-plans.index'));

    $this->assertDatabaseMissing('reading_plans', [
        'id' => $readingPlan->id,
    ]);

    $newTargetDate = today()->addWeeks(3)->format('Y-m-d');

    $storeResponse = $this->actingAs($user)
        ->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $newTargetDate,
        ]);

    $storeResponse->assertRedirect(route('reading-plans.index'));
    $storeResponse->assertSessionHas('success', '読書計画を登録しました。');

    $this->assertDatabaseHas('reading_plans', [
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingPlanStatus::Planned->value,
        'target_date' => $newTargetDate,
    ]);
}
public function test_reading_plan_index_displays_only_logged_in_users_plans(): void
{
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownBook = Book::factory()->create([
        'title' => '自分の読書計画',
    ]);

    $otherBook = Book::factory()->create([
        'title' => '他人の読書計画',
    ]);

    ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'book_id' => $ownBook->id,
        'status' => ReadingPlanStatus::Planned,
    ]);

    ReadingPlan::factory()->create([
        'user_id' => $otherUser->id,
        'book_id' => $otherBook->id,
        'status' => ReadingPlanStatus::Planned,
    ]);

    $response = $this->actingAs($user)
        ->get(route('reading-plans.index'));

    $response->assertOk();
    $response->assertSee('自分の読書計画');
    $response->assertDontSee('他人の読書計画');
}

public function test_owner_can_update_planned_reading_plan_target_date(): void
{
    $user = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Planned,
        'target_date' => today()->addWeek(),
    ]);

    $newTargetDate = today()->addWeeks(2)->format('Y-m-d');

    $response = $this->actingAs($user)
        ->put(route('reading-plans.update', $plan), [
            'target_date' => $newTargetDate,
        ]);

    $response->assertRedirect(route('reading-plans.index'));

    $this->assertDatabaseHas('reading_plans', [
        'id' => $plan->id,
        'target_date' => $newTargetDate,
        'status' => ReadingPlanStatus::Planned->value,
    ]);
}

public function test_expired_reading_plan_cannot_be_started(): void
{
    $user = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Expired,
    ]);

    $response = $this->actingAs($user)
        ->post(route('reading-plans.start', $plan));

    $response->assertForbidden();

    $this->assertSame(
        ReadingPlanStatus::Expired,
        $plan->fresh()->status
    );
}

public function test_completed_reading_plan_cannot_be_started(): void
{
    $user = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Completed,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('reading-plans.start', $plan));

    $response->assertForbidden();

    $this->assertSame(
        ReadingPlanStatus::Completed,
        $plan->fresh()->status
    );
}

public function test_owner_can_complete_planned_reading_plan(): void
{
    $user = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Planned,
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('reading-plans.complete', $plan));

    $response->assertRedirect(route('reading-plans.index'));

    $plan->refresh();

    $this->assertSame(
        ReadingPlanStatus::Completed,
        $plan->status
    );

    $this->assertNotNull($plan->completed_at);
}

public function test_owner_can_complete_reading_status_plan(): void
{
    $user = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Reading,
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('reading-plans.complete', $plan));

    $response->assertRedirect(route('reading-plans.index'));

    $this->assertSame(
        ReadingPlanStatus::Completed,
        $plan->fresh()->status
    );
}

public function test_completed_reading_plan_cannot_be_completed_again(): void
{
    $user = User::factory()->create();

    $completedAt = now()->subDay();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingPlanStatus::Completed,
        'completed_at' => $completedAt,
    ]);

    $response = $this->actingAs($user)
        ->post(route('reading-plans.complete', $plan));

    $response->assertForbidden();

    $plan->refresh();

    $this->assertSame(
        ReadingPlanStatus::Completed,
        $plan->status
    );

    $this->assertSame(
    $completedAt->format('Y-m-d H:i:s'),
    $plan->completed_at->format('Y-m-d H:i:s')
);
}

public function test_other_user_cannot_update_reading_plan(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $owner->id,
        'status' => ReadingPlanStatus::Planned,
    ]);

    $response = $this->actingAs($otherUser)
        ->put(route('reading-plans.update', $plan), [
            'target_date' => today()->addMonth()->format('Y-m-d'),
        ]);

    $response->assertForbidden();
}

public function test_other_user_cannot_complete_reading_plan(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $owner->id,
        'status' => ReadingPlanStatus::Planned,
    ]);

    $response = $this->actingAs($otherUser)
        ->post(route('reading-plans.complete', $plan));

    $response->assertForbidden();

    $this->assertSame(
        ReadingPlanStatus::Planned,
        $plan->fresh()->status
    );
}

public function test_other_user_cannot_delete_reading_plan(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $plan = ReadingPlan::factory()->create([
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($otherUser)
        ->delete(route('reading-plans.destroy', $plan));

    $response->assertForbidden();

    $this->assertDatabaseHas('reading_plans', [
        'id' => $plan->id,
    ]);
}
}