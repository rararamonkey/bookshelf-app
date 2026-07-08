<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            マイ読書レポート
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">
                    マイ読書レポート
                </h3>

                <div class="space-y-4">

    <div>
        <strong>総レビュー数：</strong>
        {{ $totalReviews }} 件
    </div>

    <div>
        <strong>読了冊数：</strong>
        {{ $completedBooks }} 冊
    </div>

    <div>
        <strong>平均評価：</strong>
        {{ $averageRating }}
    </div>

    <div>
    <strong>評価分布</strong>

    @foreach ($ratingDistribution as $rating => $count)
        <div>
            {{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }}
            ：{{ $count }} 件
        </div>
    @endforeach
</div>

    <div class="mt-6">
    <strong>高評価書籍TOP5</strong>

    @forelse($topBooks as $review)
        <div class="mt-2">
            <a href="{{ route('books.show', $review->book) }}"
                class="text-blue-600 hover:underline">
                {{ $review->book->title }}
            </a>

            （★{{ $review->rating }}）
        </div>
    @empty
        <p>該当する書籍はありません。</p>
    @endforelse
</div>

    <div class="mt-6">
    <strong>ジャンル別評価TOP5</strong>

    @forelse($genreRatings as $genre)
        <div class="mt-2">
            <a href="{{ route('genres.show', $genre->id) }}"
                class="text-blue-600 hover:underline">
                {{ $genre->name }}
            </a>

            （★{{ number_format($genre->average_rating, 1) }}）
            {{ $genre->reviews_count }}件
        </div>
    @empty
        <p>該当するジャンルはありません。</p>
    @endforelse
</div>

</div>
            </div>
        </div>
    </div>
</x-app-layout>