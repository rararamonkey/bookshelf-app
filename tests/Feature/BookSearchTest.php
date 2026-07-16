<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_searched_by_title(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP基礎',
            'author' => '佐藤花子',
        ]);

        $response = $this->get('/books?keyword=Laravel');

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP基礎');
    }

    public function test_books_can_be_searched_by_author(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP基礎',
            'author' => '佐藤花子',
        ]);

        $response = $this->get('/books?keyword=佐藤');

        $response->assertOk();
        $response->assertSee('PHP基礎');
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $programmingGenre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programmingBook = Book::factory()->create([
            'title' => 'Laravel実践',
        ]);

        $novelBook = Book::factory()->create([
            'title' => 'テスト小説',
        ]);

        $programmingBook->genres()->attach($programmingGenre->id);
        $novelBook->genres()->attach($novelGenre->id);

        $response = $this->get(
            "/books?genre={$programmingGenre->id}"
        );

        $response->assertOk();
        $response->assertSee('Laravel実践');
        $response->assertDontSee('テスト小説');
    }

    public function test_books_are_sorted_by_latest(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=latest');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '新しい書籍',
                '古い書籍',
            ]);
    }

    public function test_books_are_sorted_by_oldest(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=oldest');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '古い書籍',
                '新しい書籍',
            ]);
    }

    public function test_books_are_sorted_by_title(): void
    {
        Book::factory()->create([
            'title' => 'あいうえお',
        ]);

        Book::factory()->create([
            'title' => 'かきくけこ',
        ]);

        Book::factory()->create([
            'title' => 'さしすせそ',
        ]);

        $response = $this->get('/books?sort=title');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'あいうえお',
                'かきくけこ',
                'さしすせそ',
            ]);
    }

    public function test_books_are_sorted_by_average_rating(): void
    {
        $highRatedBook = Book::factory()->create([
            'title' => '高評価の書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価の書籍',
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        $response = $this->get('/books?sort=rating');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '高評価の書籍',
                '低評価の書籍',
            ]);
    }

    public function test_search_conditions_are_kept_in_pagination_links(): void
    {
        Book::factory()
            ->count(11)
            ->create([
                'author' => 'テスト著者',
            ]);

        $response = $this->get(
            '/books?keyword=テスト&sort=oldest'
        );

        $response->assertOk();

        $response->assertSee(
            'keyword=%E3%83%86%E3%82%B9%E3%83%88',
            false
        );

        $response->assertSee(
            'sort=oldest',
            false
        );
    }

    public function test_keyword_and_genre_filter_can_be_used_together(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $targetBook = Book::factory()->create([
            'title' => 'Laravel完全ガイド',
        ]);

        $differentKeywordBook = Book::factory()->create([
            'title' => 'PHP完全ガイド',
        ]);

        Book::factory()->create([
            'title' => 'Laravel小説',
        ]);

        $targetBook->genres()->attach($genre->id);
        $differentKeywordBook->genres()->attach($genre->id);

        $response = $this->get(
            "/books?keyword=Laravel&genre={$genre->id}"
        );

        $response->assertOk();
        $response->assertSee('Laravel完全ガイド');
        $response->assertDontSee('PHP完全ガイド');
        $response->assertDontSee('Laravel小説');
    }
}
