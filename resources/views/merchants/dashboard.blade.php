@php
    $usagePercent = $usage['included_units'] > 0 ? min(100, round($usage['total_units'] / $usage['included_units'] * 100)) : 0;
    $maxTrend = max(1, $usageTrend->max('units'));
@endphp

<x-layouts.app title="Dashboard">
    <h1 class="text-xl font-semibold mb-1">{{ $merchant->name }}</h1>
    <p class="text-sm text-gray-500 mb-6">Merchant Dashboard</p>

    {{-- Top row: stat cards --}}
    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Current Cycle Usage</p>
            <p class="text-lg font-semibold mt-1">{{ number_format($usage['total_units']) }} / {{ number_format($usage['included_units']) }} units</p>
            <div class="h-1.5 rounded-full bg-gray-100 mt-2 overflow-hidden">
                <div class="h-full bg-indigo-500" style="width: {{ $usagePercent }}%"></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Projected Overage Revenue</p>
            <p class="text-lg font-semibold mt-1">{{ \App\Support\Money::format($projectedOverageRevenue) }}</p>
            <p class="text-xs text-gray-400 mt-2">This cycle, based on usage so far</p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Active Subscriptions</p>
            <p class="text-lg font-semibold mt-1">{{ number_format($activeSubscriptionCount) }}</p>
            <p class="text-xs text-gray-400 mt-2">Currently billing customers</p>
        </div>
    </div>

    {{-- Middle row: top customers + churn risk --}}
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Top 5 Customers by Usage (this cycle)</h2>
            </div>
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2">Customer</th>
                        <th class="px-4 py-2">Usage</th>
                        <th class="px-4 py-2">% of Allowance</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($topCustomers as $row)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $row->customer_name }}</td>
                            <td class="px-4 py-2">{{ number_format($row->total_units) }}</td>
                            <td class="px-4 py-2">{{ $row->percentage }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">No usage recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-red-50 border border-red-100 rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-red-100">
                <h2 class="text-sm font-semibold text-red-700">&#9888; Churn Risk (usage &darr; &gt;50% MoM)</h2>
            </div>
            <ul class="divide-y divide-red-100">
                @forelse ($churnRisk as $row)
                    <li class="px-4 py-2.5 text-sm flex items-center justify-between">
                        <span class="font-medium text-gray-800">{{ $row->customer_name }}</span>
                        <span class="text-red-600">{{ $row->drop_percentage }}% drop</span>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-400 text-sm">No churn risk detected.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Bottom row: trend chart + system status --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Daily Usage Trend (last 30 days)</h2>
            <div class="flex items-end gap-0.5 h-32">
                @foreach ($usageTrend as $day)
                    <div class="flex-1 bg-indigo-400 rounded-t hover:bg-indigo-500 transition"
                        style="height: {{ $day['units'] > 0 ? max(4, round($day['units'] / $maxTrend * 100)) : 2 }}%"
                        title="{{ $day['date'] }}: {{ number_format($day['units']) }} units"></div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-2">
                <span>{{ $usageTrend->first()['date'] }}</span>
                <span>{{ $usageTrend->last()['date'] }}</span>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
            <h2 class="text-sm font-semibold text-blue-800 mb-3">System Status</h2>
            <ul class="space-y-2 text-sm text-blue-900">
                <li>Plan pricing cache: {{ config('cache.default') }} store, TTL 10m</li>
                <li>Aggregation job: queued, chunked (5K rows/batch), hourly</li>
                <li>Usage endpoint: rate-limited 120 req/min per API key</li>
            </ul>
        </div>
    </div>
</x-layouts.app>
