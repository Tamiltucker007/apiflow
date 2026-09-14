@php
    $maxTrend = max(1, $usageTrend->max('units'));
    $usageColor = $percentage >= 100 ? '#f59e0b' : 'var(--brand-from)';
    $remainingUnits = max(0, $plan->included_units - $usedUnits);
@endphp

<x-layouts.portal title="Usage Details">
    <h1 class="text-xl font-semibold text-gray-900 mb-1">Usage Details</h1>
    <p class="text-sm text-gray-500 mb-6">How much of this cycle's allowance you've used so far.</p>

    @if ($percentage >= 100)
        <x-alert type="error" class="mb-6">
            You've used up your plan's included units — every additional unit this cycle is billed at the overage rate.
        </x-alert>
    @elseif ($percentage >= 90)
        <x-alert type="warning" class="mb-6">
            You've used {{ $percentage }}% of this cycle's included units — you're close to the limit.
        </x-alert>
    @endif

    <div class="grid lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:col-span-1">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">This Cycle</h2>
            <dl class="text-sm divide-y divide-gray-100 mb-4">
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Included Units</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($plan->included_units) }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Used</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($usedUnits) }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Remaining</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($remainingUnits) }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Overage Units</dt>
                    <dd class="font-medium {{ $overageUnits > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ number_format($overageUnits) }}</dd>
                </div>
            </dl>

            <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                <span>Usage</span>
                <span class="font-medium">{{ $percentage }}%</span>
            </div>
            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $percentage }}%; background: {{ $usageColor }}"></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Daily Usage Trend (last 30 days)</h2>
            <div class="flex items-end gap-0.5 h-40">
                @foreach ($usageTrend as $day)
                    <div class="flex-1 rounded-t transition"
                        style="height: {{ $day['units'] > 0 ? max(4, round($day['units'] / $maxTrend * 100)) : 2 }}%; background: var(--brand-from); opacity: {{ $day['units'] > 0 ? '0.75' : '0.15' }}"
                        title="{{ $day['date'] }}: {{ number_format($day['units']) }} units"></div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-2">
                <span>{{ $usageTrend->first()['date'] }}</span>
                <span>{{ $usageTrend->last()['date'] }}</span>
            </div>
        </div>
    </div>
</x-layouts.portal>
