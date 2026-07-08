<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    return [
        'user_id' => \App\Models\User::factory(),
        'title' => $this->faker->sentence(3),
        'author' => $this->faker->name(),
        'isbn' => $this->faker->unique()->numerify('#############'),
        'published_date' => $this->faker->date(),
        'description' => $this->faker->paragraph(),
        'image_url' => 'https://placehold.co/200x300',
    ];
}
}
