<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        foreach ($users as $user) {
            $favoriteBooks = $books->random(rand(3, 5));

            foreach ($favoriteBooks as $book) {
                $user->favoriteBooks()->syncWithoutDetaching($book->id);
            }
        }
    }
}