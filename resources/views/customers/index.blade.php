<x-layouts.app title="Customers">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Customers</h1>
        <a href="{{ route('merchants.customers.create', $merchant) }}"
            class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">+ Register Customer</a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">External ID</th>
                    <th class="px-4 py-3">Registered</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($customers as $customer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('merchants.customers.show', [$merchant, $customer]) }}" class="text-indigo-600 hover:underline">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $customer->external_id ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $customer->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">No customers registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
