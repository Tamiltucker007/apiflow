@php($canManage = auth()->user()->role !== \App\Enums\UserRole::MerchantStaff)

<x-layouts.app title="Subscriptions">
    <h1 class="text-xl font-semibold mb-6">Subscriptions</h1>

    @if ($errors->any())
        <x-alert class="mb-6 max-w-xl">{{ $errors->first() }}</x-alert>
    @endif

    @if ($canManage)
        <div class="bg-white rounded-lg shadow p-6 max-w-xl mb-8">
            <h2 class="text-sm font-medium text-gray-700 mb-4">Subscribe a customer to a plan</h2>

            <form method="POST" action="{{ route('merchants.subscriptions.store', $merchant) }}" class="flex flex-wrap items-end gap-4">
                @csrf

                <div class="flex-1 min-w-[180px]">
                    <label class="block text-sm font-medium text-gray-700">Customer</label>
                    <select name="customer_id" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <label class="block text-sm font-medium text-gray-700">Plan</label>
                    <select name="plan_id" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select plan</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ \App\Support\Money::format($plan->base_price_cents, $plan->currency) }})</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">
                    Subscribe
                </button>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Current Period</th>
                    @if ($canManage)
                        <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($subscriptions as $subscription)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $subscription->customer->name }}</td>
                        <td class="px-4 py-3">{{ $subscription->plan->name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">
                                {{ $subscription->status->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $subscription->current_period_start->format('d M Y') }} – {{ $subscription->current_period_end->format('d M Y') }}
                        </td>
                        @if ($canManage)
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <form method="POST" action="{{ route('merchants.subscriptions.generate-invoice', [$merchant, $subscription]) }}">
                                        @csrf
                                        <button class="text-indigo-600 hover:underline text-xs whitespace-nowrap">Generate Invoice</button>
                                    </form>

                                    <form method="POST" action="{{ route('merchants.subscriptions.change-plan', [$merchant, $subscription]) }}" class="flex items-center gap-1">
                                        @csrf
                                        @method('PUT')
                                        <select name="plan_id" class="rounded border-gray-300 text-xs py-1 focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->id }}" @selected($plan->id === $subscription->plan_id)>{{ $plan->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="text-indigo-600 hover:underline text-xs whitespace-nowrap">Change</button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 5 : 4 }}" class="px-4 py-6 text-center text-gray-400">No subscriptions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
