<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_planned_reading_plan_is_changed_to_expired_and_notification_is_created(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Planned,
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->status
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => ReadingPlanReminder::class,
        ]);
    }

    public function test_expired_reading_status_plan_is_changed_to_expired(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->status
        );
    }

    public function test_plan_due_today_is_not_changed_or_notified(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today(),
            'status' => ReadingPlanStatus::Planned,
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Planned,
            $plan->status
        );

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => ReadingPlanReminder::class,
        ]);
    }

    public function test_future_plan_is_not_changed_or_notified(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addWeek(),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $plan->status
        );

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => ReadingPlanReminder::class,
        ]);
    }

    public function test_completed_plan_is_not_changed_or_notified(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => ReadingPlanReminder::class,
        ]);
    }

    public function test_same_reading_plan_is_not_notified_twice(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Planned,
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertSame(
            1,
            $user->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->where('data->reading_plan_id', $plan->id)
                ->count()
        );

        // 2回目も期限切れ処理の対象に戻し、
        // 既存通知の重複確認が働くことを確認する。
        $plan->update([
            'status' => ReadingPlanStatus::Planned,
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertSame(
            1,
            $user->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->where('data->reading_plan_id', $plan->id)
                ->count()
        );
    }
}