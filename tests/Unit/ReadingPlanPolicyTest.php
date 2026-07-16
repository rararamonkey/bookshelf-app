<?php

namespace Tests\Unit;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Policies\ReadingPlanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_planned_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Planned,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertTrue($policy->update($user, $plan));
    }

    public function test_owner_can_update_reading_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Reading,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertTrue($policy->update($user, $plan));
    }

    public function test_owner_can_update_expired_reading_plan(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Expired,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertTrue($policy->update($user, $plan));
    }

    public function test_completed_reading_plan_cannot_be_updated(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertFalse($policy->update($user, $plan));
    }

    public function test_only_planned_reading_plan_can_be_started(): void
    {
        $user = User::factory()->create();

        $planned = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Planned,
        ]);

        $reading = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create()->id,
            'status' => ReadingPlanStatus::Reading,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertTrue($policy->start($user, $planned));
        $this->assertFalse($policy->start($user, $reading));
    }

    public function test_owner_can_complete_planned_reading_and_expired_plans(): void
    {
        $user = User::factory()->create();
        $policy = new ReadingPlanPolicy;

        foreach ([
            ReadingPlanStatus::Planned,
            ReadingPlanStatus::Reading,
            ReadingPlanStatus::Expired,
        ] as $status) {
            $plan = ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => Book::factory()->create()->id,
                'status' => $status,
            ]);

            $this->assertTrue($policy->complete($user, $plan));
        }
    }

    public function test_completed_reading_plan_cannot_be_completed_again(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertFalse($policy->complete($user, $plan));
    }

    public function test_other_user_cannot_operate_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::Planned,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertFalse($policy->update($otherUser, $plan));
        $this->assertFalse($policy->start($otherUser, $plan));
        $this->assertFalse($policy->complete($otherUser, $plan));
        $this->assertFalse($policy->delete($otherUser, $plan));
    }
}
