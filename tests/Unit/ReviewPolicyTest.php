<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\User;
use App\Policies\ReviewPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_update_own_review(): void
    {
        $author = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $policy = new ReviewPolicy();

        $this->assertTrue($policy->update($author, $review));
    }

    public function test_other_user_cannot_update_review(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $policy = new ReviewPolicy();

        $this->assertFalse($policy->update($otherUser, $review));
    }

    public function test_author_can_delete_own_review(): void
    {
        $author = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $policy = new ReviewPolicy();

        $this->assertTrue($policy->delete($author, $review));
    }

    public function test_other_user_cannot_delete_review(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $policy = new ReviewPolicy();

        $this->assertFalse($policy->delete($otherUser, $review));
    }
}