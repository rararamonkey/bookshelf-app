<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_genres(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)
            ->get('/genres');

        $response->assertStatus(200);
        $response->assertSee('テストジャンル');
    }

    public function test_authenticated_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/genres', [
                'name' => 'PHP',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('genres', [
            'name' => 'PHP',
        ]);
    }

    public function test_genre_cannot_be_created_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/genres', []);

        $response->assertSessionHasErrors([
            'name',
        ]);
    }
}
