<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">
            読書計画作成
        </h2>
    </x-slot>

    <div class="p-6">

        <form method="POST" action="{{ route('reading-plans.store') }}">
            @csrf

            <div class="mb-4">
                <label>書籍</label>

                <select name="book_id" class="border rounded w-full">
                    @foreach($books as $book)
                        <option value="{{ $book->id }}">
                            {{ $book->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label>期限</label>

                <input
                    type="date"
                    name="due_date"
                    class="border rounded w-full"
                >
            </div>

            <button
                class="bg-blue-500 text-white px-4 py-2 rounded"
            >
                登録
            </button>

        </form>

    </div>
</x-app-layout>