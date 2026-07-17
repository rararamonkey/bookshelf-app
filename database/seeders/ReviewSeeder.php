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
            1 => '期待していた内容とは少し異なりました。',
            2 => '参考になる部分もありました。',
            3 => '読みやすく、標準的な内容でした。',
            4 => 'とても参考になり、学びの多い本でした。',
            5 => '非常に満足でき、何度も読み返したい一冊です。',
        ];

        /*
         * 11冊に最低2件ずつ割り当てると22件。
         * 残り10件をランダムに追加し、合計32件にする。
         * 各書籍のレビュー件数は最大4件。
         */
        $reviewCounts = array_fill(0, $books->count(), 2);
        $remainingCount = 32 - array_sum($reviewCounts);

        while ($remainingCount > 0) {
            $bookIndex = array_rand($reviewCounts);

            if ($reviewCounts[$bookIndex] < 4) {
                $reviewCounts[$bookIndex]++;
                $remainingCount--;
            }
        }

        /*
         * 評価1〜5が必ず一度以上含まれるようにしたうえで、
         * 残りの評価を1〜5からランダムに作成する。
         */
        $ratings = [1, 2, 3, 4, 5];

        while (count($ratings) < 32) {
            $ratings[] = rand(1, 5);
        }

        shuffle($ratings);

        $ratingIndex = 0;

        foreach ($books as $bookIndex => $book) {
            $reviewCount = $reviewCounts[$bookIndex];

            $reviewUsers = $users
                ->shuffle()
                ->take($reviewCount);

            foreach ($reviewUsers as $user) {
                $rating = $ratings[$ratingIndex];

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => $comments[$rating],
                ]);

                $ratingIndex++;
            }
        }
    }
}
