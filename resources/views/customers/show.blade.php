@php($canManageKeys = auth()->user()->role !== \App\Enums\UserRole::MerchantStaff)

<x-layouts.app :title="$customer->name">
    <a href="{{ route('merchants.customers.index', $merchant) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Customers</a>

    <div class="flex items-center justify-between mt-2 mb-6">
        <div>
            <h1 class="text-xl font-semibold">{{ $customer->name }}</h1>
            <p class="text-sm text-gray-500">{{ $customer->email }}</p>
        </div>
    </div>

    @if (session('newApiKey'))
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="text-sm font-medium text-amber-800 mb-2">
                New API key generated — copy it now, it won't be shown again.
            </p>
            <code class="block bg-white border border-amber-200 rounded px-3 py-2 text-sm text-gray-800 break-all select-all">{{ session('newApiKey') }}</code>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700">API Keys</h2>

            @if ($canManageKeys)
                <form method="POST" action="{{ route('merchants.customers.api-keys.store', [$merchant, $customer]) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="name" placeholder="Key label (optional)"
                        class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="bg-indigo-600 text-white text-sm px-3 py-2 rounded-lg hover:bg-indigo-700">
                        + Generate New Key
                    </button>
                </form>
            @endif
        </div>

        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Prefix</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3">Last Used</th>
                    <th class="px-4 py-3">Status</th>
                    @if ($canManageKeys)
                        <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($apiKeys as $key)
                    <tr>
                        <td class="px-4 py-3 font-mono text-gray-600">{{ $key->key_prefix }}…</td>
                        <td class="px-4 py-3">{{ $key->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $key->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $key->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs {{ $key->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $key->is_active ? 'Active' : 'Revoked' }}
                            </span>
                        </td>
                        @if ($canManageKeys)
                            <td class="px-4 py-3 text-right">
                                @if ($key->is_active)
                                    <form method="POST" action="{{ route('merchants.customers.api-keys.destroy', [$merchant, $customer, $key]) }}"
                                        onsubmit="return confirm('Revoke this key? Any application using it will stop working immediately.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline text-xs">Revoke</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManageKeys ? 6 : 5 }}" class="px-4 py-6 text-center text-gray-400">No API keys yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
