@php
    $percentage = $plan->included_units > 0 ? min(100, (int) round($usedUnits / $plan->included_units * 100)) : 0;
    $maxTrend = max(1, $usageTrend->max('units'));

    // Semantic color (amber = "pay attention") stays fixed regardless of
    // merchant brand — usage crossing the allowance means the same thing
    // for every merchant, so it shouldn't blend into whichever brand color
    // that merchant happens to have.
    $usageColor = $percentage >= 100 ? '#f59e0b' : 'var(--brand-from)';

    $statusStyles = [
        'draft' => 'bg-gray-100 text-gray-600',
        'pending' => 'bg-amber-100 text-amber-700',
        'paid' => 'bg-emerald-100 text-emerald-700',
        'failed' => 'bg-red-100 text-red-700',
    ];
@endphp

<x-layouts.portal title="Dashboard">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 rounded-xl text-white flex items-center justify-center flex-shrink-0 shadow-sm"
            style="background: linear-gradient(135deg, var(--brand-from), var(--brand-to))">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M4 19V9M10 19V5M16 19v-7M22 19H2" stroke="white" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Welcome, {{ $customer->name }}</h1>
            <p class="text-sm text-gray-500">Here's your usage and billing for this cycle.</p>
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                    style="background: color-mix(in srgb, var(--brand-from) 12%, white)">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                        <path d="M10 2.5 3 6l7 3.5 7-3.5-7-3.5ZM3 10l7 3.5 7-3.5M3 14l7 3.5 7-3.5" stroke="var(--brand-from)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Active Plan</p>
            </div>
            <p class="text-lg font-semibold text-gray-900">{{ $plan->name }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $subscription->current_period_start->format('d M') }} – {{ $subscription->current_period_end->format('d M Y') }}
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                    style="background: color-mix(in srgb, {{ $percentage >= 100 ? '#f59e0b' : 'var(--brand-from)' }} 12%, white)">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                        <path d="M3 17V9M8 17V3M13 17v-6M18 17v-3" stroke="{{ $usageColor }}" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Usage This Cycle</p>
            </div>
            <p class="text-lg font-semibold text-gray-900">{{ number_format($usedUnits) }} <span class="text-sm font-normal text-gray-400">/ {{ number_format($plan->included_units) }} units</span></p>
            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden mt-2.5">
                <div class="h-full rounded-full" style="width: {{ $percentage }}%; background: {{ $usageColor }}"></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $overageUnits > 0 ? 'bg-amber-50' : 'bg-gray-50' }}">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                        <path d="M10 6.5v4M10 13.5h.01" stroke="{{ $overageUnits > 0 ? '#d97706' : '#9ca3af' }}" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="10" r="7.5" stroke="{{ $overageUnits > 0 ? '#d97706' : '#9ca3af' }}" stroke-width="1.5"/>
                    </svg>
                </div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Overage Units</p>
            </div>
            <p class="text-lg font-semibold {{ $overageUnits > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ number_format($overageUnits) }}</p>
            <p class="text-xs text-gray-400 mt-1">at {{ \App\Support\Money::format($plan->overage_rate_cents, $plan->currency) }} / unit</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Daily Usage Trend (last 30 days)</h2>
        <div class="flex items-end gap-0.5 h-24">
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

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Invoices</h2>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3">Invoice</th>
                    <th class="px-5 py-3">Period</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($invoices as $invoice)
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-5 py-3">
                            <a href="{{ route('invoices.show', $invoice) }}" class="font-medium hover:underline" style="color: var(--brand-from)">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $invoice->period_start->format('d M') }} – {{ $invoice->period_end->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded text-xs capitalize {{ $statusStyles[$invoice->status->value] ?? 'bg-gray-100 text-gray-600' }}">{{ $invoice->status->value }}</span>
                        </td>
                        <td class="px-5 py-3 text-right font-medium text-gray-900">{{ \App\Support\Money::format($invoice->total_amount_cents, $invoice->currency) }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('invoices.download', $invoice) }}" class="text-xs hover:underline" style="color: var(--brand-from)">Download</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                            <svg class="mx-auto mb-2 text-gray-300" width="28" height="28" viewBox="0 0 24 24" fill="none">
                                <path d="M6 3h8l4 4v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M9 12h6M9 15.5h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            No invoices yet — they'll show up here once your first billing cycle ends.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</x-layouts.portal>
