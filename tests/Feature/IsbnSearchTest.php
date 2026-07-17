<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_information_can_be_fetched_by_valid_isbn(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['テスト著者'],
                            'publishedDate' => '2026-07-01',
                            'description' => 'テスト説明',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.fetchByIsbn', '9781234567890'));

        $response
            ->assertOk()
            ->assertJson([
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'published_date' => '2026-07-01',
                'description' => 'テスト説明',
                'image_url' => 'https://example.com/book.jpg',
            ]);
        Http::assertSent(function ($request): bool {
            $queryParameters = [];

            parse_str(
                parse_url($request->url(), PHP_URL_QUERY) ?? '',
                $queryParameters
            );

            return parse_url($request->url(), PHP_URL_SCHEME) === 'https'
                && parse_url($request->url(), PHP_URL_HOST)
                    === 'www.googleapis.com'
                && parse_url($request->url(), PHP_URL_PATH)
                    === '/books/v1/volumes'
                && ($queryParameters['q'] ?? null)
                    === 'isbn:9781234567890'
                && ($queryParameters['key'] ?? null)
                    === config('services.google_books.api_key');
        });
    }

    public function test_invalid_isbn_returns_422(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $response = $this->actingAs($user)
            ->getJson(route('books.fetchByIsbn', '123'));

        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);

        Http::assertNothingSent();
    }

    public function test_not_found_book_returns_404(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.fetchByIsbn', '9781234567890'));

        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '書籍情報が見つかりませんでした。',
            ]);
    }

    public function test_google_books_api_failure_returns_500(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response(
                [],
                500
            ),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.fetchByIsbn', '9781234567890'));

        $response
            ->assertInternalServerError()
            ->assertJson([
                'error' => '書籍情報の取得に失敗しました。',
            ]);
    }

    public function test_guest_cannot_use_isbn_search(): void
    {
        Http::fake();

        $response = $this
            ->getJson(route('books.fetchByIsbn', '9781234567890'));

        $response->assertUnauthorized();

        Http::assertNothingSent();
    }
}
