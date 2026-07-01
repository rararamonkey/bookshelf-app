<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $likeUsers = $users
    ->where('id', '!=', $review->user_id)
    ->shuffle()
    ->take(rand(0, 3));

            foreach ($likeUsers as $user) {
                $review->likedByUsers()->syncWithoutDetaching($user->id);
            }
        }
    }
}