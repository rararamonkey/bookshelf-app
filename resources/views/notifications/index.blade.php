<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">
            通知一覧
        </h2>
    </x-slot>

    <div class="p-6">
        @if(session('success'))
            <div class="mb-4 rounded bg-green-100 border border-green-400 text-green-700 px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        @forelse($notifications as $notification)
            <div class="mb-4 border rounded p-4 {{ is_null($notification->read_at) ? 'bg-yellow-50' : 'bg-white' }}">
                <div class="font-bold">
                    {{ $notification->data['message'] ?? '通知があります。' }}
                </div>

                <div class="text-sm text-gray-500 mt-1">
                    {{ $notification->created_at->format('Y/m/d H:i') }}
                </div>

                <div class="mt-3 flex gap-3">
                    <a href="{{ $notification->data['url'] ?? route('reading-plans.index') }}"
                       class="text-blue-600 hover:underline">
                        読書計画を見る
                    </a>

                    @if(is_null($notification->read_at))
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="text-green-600 hover:underline">
                                既読にする
                            </button>
                        </form>
                    @else
                        <span class="text-gray-500">既読</span>
                    @endif
                </div>
            </div>
        @empty
            <p>通知はありません。</p>
        @endforelse

        {{ $notifications->links() }}
    </div>
</x-app-layout>