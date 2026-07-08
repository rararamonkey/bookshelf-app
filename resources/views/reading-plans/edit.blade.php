<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">
            読書計画編集
        </h2>
    </x-slot>

    <div class="p-6">
        <form method="POST" action="{{ route('reading-plans.update', $readingPlan) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label>書籍</label>
                <select name="book_id" class="border rounded w-full">
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" @selected(old('book_id', $readingPlan->book_id) == $book->id)>
                            {{ $book->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label>期限</label>
                <input type="date"
                       name="due_date"
                       value="{{ old('due_date', $readingPlan->due_date) }}"
                       class="border rounded w-full">
            </div>

            <div class="mb-4">
                <label>状態</label>
                <select name="status" class="border rounded w-full">
                    <option value="planned" @selected(old('status', $readingPlan->status) === 'planned')>未読</option>
                    <option value="reading" @selected(old('status', $readingPlan->status) === 'reading')>読書中</option>
                    <option value="completed" @selected(old('status', $readingPlan->status) === 'completed')>読了</option>
                </select>
            </div>

            <button class="bg-blue-500 text-white px-4 py-2 rounded">
                更新
            </button>
        </form>
    </div>
</x-app-layout>