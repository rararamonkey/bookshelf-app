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
        $users = User::all();
        $books = Book::all();

        $users->each(function (User $user) use ($books): void {
            $books->random(3)->each(function (Book $book, int $index) use ($user): void {
                $status = match ($index) {
                    0 => ReadingPlanStatus::Planned,
                    1 => ReadingPlanStatus::Reading,
                    default => ReadingPlanStatus::Completed,
                };

                ReadingPlan::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                    ],
                    [
                        'target_date' => Carbon::today()->addDays($index + 3),
                        'status' => $status,
                        'completed_at' => $status === ReadingPlanStatus::Completed ? now() : null,
                    ]
                );
            });
        });
    }
}