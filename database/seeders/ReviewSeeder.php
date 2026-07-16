<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $comments = [
            'とても読みやすく、学びが多い本でした。',
            '内容が分かりやすく、参考になりました。',
            'もう一度読み返したいと思える一冊です。',
            '考え方を見直すきっかけになりました。',
            '初心者にも理解しやすい内容でした。',
        ];

        $count = 0;

        foreach ($books as $book) {
            foreach ($users->take(3) as $user) {
                if ($count >= 32) {
                    return;
                }

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => rand(3, 5),
                    'comment' => $comments[array_rand($comments)],
                ]);

                $count++;
            }
        }
    }
}
