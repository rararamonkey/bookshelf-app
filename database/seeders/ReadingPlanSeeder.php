<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('email', [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ])->get()->keyBy('email');

        $books = Book::whereIn('isbn', [
            '9784101010014',
            '9784422100524',
            '9784873115658',
            '9784863940246',
            '9784101010021',
            '9784309226712',
            '9784048930598',
            '9784478025819',
            '9784163902302',
            '9784822289607',
            '9784822251468',
        ])->get()->keyBy('isbn');

        $today = Carbon::today();

        $plans = [
            /*
             * 山田太郎：主要な動作確認用データ
             */

            // planned・期限切れ：通知対象
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784101010014',
                'target_date' => $today->copy()->subDays(3),
                'status' => ReadingPlanStatus::Planned,
                'completed_at' => null,
            ],

            // reading・期限切れ：通知対象
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784422100524',
                'target_date' => $today->copy()->subDay(),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],

            // planned・期限当日：通知対象外
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784873115658',
                'target_date' => $today->copy(),
                'status' => ReadingPlanStatus::Planned,
                'completed_at' => null,
            ],

            // reading・未来日：通知対象外
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784863940246',
                'target_date' => $today->copy()->addDays(3),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],

            // completed・期限切れ：通知対象外
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784101010021',
                'target_date' => $today->copy()->subDays(5),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy()->subDays(4),
            ],

            // expired・期限切れ：通知対象外
            [
                'user_email' => 'yamada@example.com',
                'book_isbn' => '9784309226712',
                'target_date' => $today->copy()->subDays(7),
                'status' => ReadingPlanStatus::Expired,
                'completed_at' => null,
            ],

            /*
             * 他ユーザー：認可やユーザーごとの表示確認用データ
             */

            [
                'user_email' => 'suzuki@example.com',
                'book_isbn' => '9784048930598',
                'target_date' => $today->copy()->addDays(5),
                'status' => ReadingPlanStatus::Planned,
                'completed_at' => null,
            ],
            [
                'user_email' => 'tanaka@example.com',
                'book_isbn' => '9784478025819',
                'target_date' => $today->copy()->addDays(2),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],
            [
                'user_email' => 'sato@example.com',
                'book_isbn' => '9784163902302',
                'target_date' => $today->copy()->subDays(2),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy()->subDay(),
            ],
            [
                'user_email' => 'takahashi@example.com',
                'book_isbn' => '9784822289607',
                'target_date' => $today->copy(),
                'status' => ReadingPlanStatus::Planned,
                'completed_at' => null,
            ],
        ];

        foreach ($plans as $plan) {
            $user = $users->get($plan['user_email']);
            $book = $books->get($plan['book_isbn']);

            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                ],
                [
                    'target_date' => $plan['target_date'],
                    'status' => $plan['status'],
                    'completed_at' => $plan['completed_at'],
                ]
            );
        }
    }
}
