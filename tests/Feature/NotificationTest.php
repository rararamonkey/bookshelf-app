<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_another_users_notification_returns_not_found(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $user->notify(new ReadingPlanReminder($readingPlan));

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee($book->title);
    }

    public function test_notification_list_does_not_display_another_users_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBook = Book::factory()->create([
            'title' => '自分の通知対象書籍',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '他人の通知対象書籍',
        ]);

        $ownPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $ownBook->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $user->notify(new ReadingPlanReminder($ownPlan));
        $otherUser->notify(new ReadingPlanReminder($otherPlan));

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('自分の通知対象書籍');
        $response->assertDontSee('他人の通知対象書籍');
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $user->notify(new ReadingPlanReminder($readingPlan));

        $notification = $user->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response->assertRedirect();

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $owner->notify(new ReadingPlanReminder($readingPlan));

        $notification = $owner->notifications()->first();

        $response = $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification->id));

        $response->assertNotFound();

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $user->notify(new ReadingPlanReminder($readingPlan));

        $notification = $user->notifications()->first();

        $response = $this
            ->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('login'));

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }
}