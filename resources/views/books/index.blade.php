<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('書籍一覧') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                <a href="{{ route('books.create') }}"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    書籍を登録
                </a>
            </div>

            <form method="GET" action="{{ route('books.index') }}" class="mb-6 bg-white p-4 rounded shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">キーワード</label>
                        <input type="text" name="keyword" id="keyword" value="{{ request('keyword') }}"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                            placeholder="タイトル・著者で検索">
                    </div>

                    <div>
                        <label for="genre" class="block text-sm font-medium text-gray-700 mb-1">ジャンル</label>
                        <select name="genre" id="genre"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                            <option value="">すべて</option>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}" @selected(request('genre') == $genre->id)>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">並び順</label>
                        <select name="sort" id="sort"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                            <option value="latest" @selected(request('sort', 'latest') === 'latest')>新しい順</option>
                            <option value="oldest" @selected(request('sort') === 'oldest')>古い順</option>
                            <option value="title" @selected(request('sort') === 'title')>タイトル順</option>
                            <option value="rating" @selected(request('sort') === 'rating')>評価が高い順</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">
                            検索
                        </button>

                        <a href="{{ route('books.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">
                            クリア
                        </a>
                    </div>
                </div>
            </form>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($books->isEmpty())
                        <p class="text-gray-500">書籍が登録されていません。</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($books as $book)
                                <a href="{{ route('books.show', $book) }}"
                                    class="block border rounded-lg p-4 shadow hover:shadow-lg transition cursor-pointer">
                                    @if ($book->image_url)
                                        <img src="{{ $book->image_url }}" alt="{{ $book->title }}"
                                            class="w-full h-48 object-cover mb-4 rounded">
                                    @else
                                        <div
                                            class="w-full h-48 bg-gray-200 flex items-center justify-center mb-4 rounded">
                                            <span class="text-gray-500">画像なし</span>
                                        </div>
                                    @endif
                                    <h3 class="font-bold text-lg mb-2 text-blue-600 hover:text-blue-800">
                                        {{ $book->title }}
                                    </h3>
                                    <p class="text-gray-600 text-sm mb-2">{{ $book->author }}</p>
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @foreach ($book->genres as $genre)
                                            <span
                                                class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded">{{ $genre->name }}</span>
                                        @endforeach
                                    </div>
                                    @if ($book->reviews_avg_rating)
                                        <div class="flex items-center">
                                            <span class="text-yellow-500">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= round($book->reviews_avg_rating))
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                            </span>
                                            <span class="text-sm text-gray-500 ml-2">
                                                ({{ number_format($book->reviews_avg_rating, 1) }})
                                            </span>
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $books->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
