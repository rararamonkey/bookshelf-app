<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">
            読書計画
        </h2>
    </x-slot>

    <div class="p-6">

    @if(session('success'))
    <div class="mb-4 rounded bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('success') }}
    </div>
@endif

        <div class="mb-4">
            <a href="{{ route('reading-plans.create') }}"
               class="bg-blue-500 text-white px-4 py-2 rounded">
                新規計画作成
            </a>
        </div>
        
        <div class="mb-4 flex gap-2">
    <a href="{{ route('reading-plans.index') }}"
       class="px-3 py-1 rounded {{ request('status') === null ? 'bg-indigo-600 text-white' : 'bg-gray-200' }}">
        すべて
    </a>

    <a href="{{ route('reading-plans.index', ['status' => 'planned']) }}"
       class="px-3 py-1 rounded {{ request('status') === 'planned' ? 'bg-indigo-600 text-white' : 'bg-gray-200' }}">
        未読
    </a>

    <a href="{{ route('reading-plans.index', ['status' => 'reading']) }}"
       class="px-3 py-1 rounded {{ request('status') === 'reading' ? 'bg-indigo-600 text-white' : 'bg-gray-200' }}">
        読書中
    </a>

    <a href="{{ route('reading-plans.index', ['status' => 'completed']) }}"
       class="px-3 py-1 rounded {{ request('status') === 'completed' ? 'bg-indigo-600 text-white' : 'bg-gray-200' }}">
        読了
    </a>
</div>

        @forelse($readingPlans as $plan)
            <div class="mb-4 border rounded p-4">
                <div><strong>{{ $plan->book->title }}</strong></div>
                <div>期限：{{ $plan->due_date }}</div>

                <div>
                    状態：
                    @switch($plan->status)
                        @case('planned')
                            未読
                            @break
                        @case('reading')
                            読書中
                            @break
                        @case('completed')
                            読了
                            @break
                    @endswitch
                </div>

                <a href="{{ route('reading-plans.edit', $plan) }}"
   class="inline-block mt-2 text-blue-600 hover:underline">
    編集
</a>

<form method="POST"
      action="{{ route('reading-plans.destroy', $plan) }}"
      class="mt-2"
      onsubmit="return confirm('削除しますか？')">
    @csrf
    @method('DELETE')

    <button type="submit"
        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
        削除
    </button>
</form>
                @if($plan->status !== 'completed')
                    <form method="POST"
                          action="{{ route('reading-plans.update-status', $plan) }}"
                          class="mt-2">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                            class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                            @if($plan->status === 'planned')
                                読書中にする
                            @elseif($plan->status === 'reading')
                                読了にする
                            @endif
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <p>読書計画はありません。</p>
        @endforelse

        {{ $readingPlans->links() }}
    </div>
</x-app-layout>