<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\User;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_own_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $policy = new BookPolicy;

        $this->assertTrue($policy->update($owner, $book));
    }

    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $policy = new BookPolicy;

        $this->assertFalse($policy->update($otherUser, $book));
    }

    public function test_owner_can_delete_own_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $policy = new BookPolicy;

        $this->assertTrue($policy->delete($owner, $book));
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $policy = new BookPolicy;

        $this->assertFalse($policy->delete($otherUser, $book));
    }
}
