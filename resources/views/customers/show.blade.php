<x-layouts.app :title="$customer->name">
    <a href="{{ route('admin.customers.index', $merchant) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Customers</a>

    <div class="flex items-center justify-between mt-2 mb-6">
        <div>
            <h1 class="text-xl font-semibold">{{ $customer->name }}</h1>
            <p class="text-sm text-gray-500">{{ $customer->email }}</p>
        </div>

        @unless (app()->isProduction())
            <form method="POST" action="{{ route('admin.customers.simulate-usage', [$merchant, $customer]) }}">
                @csrf
                <button type="submit" class="bg-white border border-gray-300 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-50"
                    title="Backfills a week of usage and re-runs aggregation immediately — for demoing the usage-to-billing flow without a real API client">
                    Simulate Usage (demo)
                </button>
            </form>
        @endunless
    </div>

    @if (session('newApiKey'))
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="text-sm font-medium text-amber-800 mb-2">
                New API key generated — copy it now, it won't be shown again.
            </p>
            <code class="block bg-white border border-amber-200 rounded px-3 py-2 text-sm text-gray-800 break-all select-all">{{ session('newApiKey') }}</code>
        </div>
    @endif

    @if (session('newPortalPassword'))
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="text-sm font-medium text-amber-800 mb-2">
                New portal password generated — copy it and share it with the customer now, it won't be shown again.
            </p>
            <code class="block bg-white border border-amber-200 rounded px-3 py-2 text-sm text-gray-800 break-all select-all">{{ session('newPortalPassword') }}</code>
            <p class="text-xs text-amber-700 mt-2">They can log in at <span class="font-mono">{{ route('login') }}</span> with {{ $customer->email }}.</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-700">Account Status</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Status:
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $customer->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @unless ($customer->is_active)
                        <span class="text-gray-400">— login and API access are blocked until reactivated.</span>
                    @endunless
                </p>
            </div>

            <form method="POST" action="{{ route('admin.customers.toggle', [$merchant, $customer]) }}"
                @if ($customer->is_active)
                    data-confirm="Deactivate {{ $customer->name }}? They won't be able to log in or use their API keys until reactivated."
                    data-confirm-title="Deactivate Customer" data-confirm-variant="warning" data-confirm-action="Deactivate"
                @endif>
                @csrf
                @method('PUT')
                <button type="submit" class="bg-white border border-gray-300 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-50">
                    {{ $customer->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-1">
            <div>
                <h2 class="text-sm font-semibold text-gray-700">Customer Portal Access</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Lets {{ $customer->name }} log in and see their own usage and invoices.
                    Status:
                    <span class="{{ $customer->password ? 'text-green-600' : 'text-gray-400' }} font-medium">
                        {{ $customer->password ? 'Portal access enabled' : 'No password set' }}
                    </span>
                </p>
            </div>

            <form method="POST" action="{{ route('admin.customers.portal-password.store', [$merchant, $customer]) }}"
                @if ($customer->password)
                    data-confirm="This replaces {{ $customer->name }}'s current portal password — they will need the new one to log in."
                    data-confirm-title="Reset Portal Password" data-confirm-variant="warning" data-confirm-action="Reset Password"
                @endif>
                @csrf
                <button type="submit" class="bg-white border border-gray-300 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-50">
                    {{ $customer->password ? 'Reset Portal Password' : 'Enable Portal Access' }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700">API Keys</h2>

            <form method="POST" action="{{ route('admin.customers.api-keys.store', [$merchant, $customer]) }}" class="flex items-center gap-2">
                @csrf
                <input type="text" name="name" placeholder="Key label (optional)" autocomplete="off"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition">
                <button type="submit" class="bg-indigo-600 text-white text-sm px-3 py-2 rounded-lg hover:bg-indigo-700">
                    + Generate New Key
                </button>
            </form>
        </div>

        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Prefix</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3">Last Used</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
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
                        <td class="px-4 py-3 text-right">
                            @if ($key->is_active)
                                <form method="POST" action="{{ route('admin.customers.api-keys.destroy', [$merchant, $customer, $key]) }}"
                                    data-confirm="Revoke this key? Any application using it will stop working immediately."
                                    data-confirm-title="Revoke API Key" data-confirm-variant="danger" data-confirm-action="Revoke">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Revoke</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">No API keys yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
