<x-layouts.app title="Plans">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Plans</h1>
        <a href="{{ route('merchants.plans.create', $merchant) }}"
            class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">+ New Plan</a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Billing Cycle</th>
                    <th class="px-4 py-3">Base Price</th>
                    <th class="px-4 py-3">Included Units</th>
                    <th class="px-4 py-3">Overage Rate</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($plans as $plan)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $plan->name }}</td>
                        <td class="px-4 py-3 capitalize">{{ $plan->billing_cycle->value }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Money::format($plan->base_price_cents, $plan->currency) }}</td>
                        <td class="px-4 py-3">{{ number_format($plan->included_units) }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Money::format($plan->overage_rate_cents, $plan->currency) }} / unit</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('merchants.plans.toggle', [$merchant, $plan]) }}">
                                @csrf
                                @method('PUT')
                                <button class="text-indigo-600 hover:underline text-xs">
                                    {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">No plans yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
