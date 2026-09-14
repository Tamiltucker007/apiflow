@php
    $percentage = $plan && $plan->included_units > 0 ? min(100, (int) round($usedUnits / $plan->included_units * 100)) : 0;
@endphp

<x-layouts.portal title="Dashboard">
    <h1 class="text-xl font-semibold mb-1">Welcome, {{ $customer->name }}</h1>
    <p class="text-sm text-gray-500 mb-6">Here's your usage and billing for this cycle.</p>

    @if (! $subscription)
        <div class="bg-white rounded-lg shadow p-6 text-sm text-gray-500">
            You don't have an active subscription right now.
        </div>
    @else
        <div class="grid sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500 text-sm">Active Plan</p>
                <p class="text-lg font-semibold">{{ $plan->name }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $subscription->current_period_start->format('d M') }} – {{ $subscription->current_period_end->format('d M Y') }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500 text-sm">Usage This Cycle</p>
                <p class="text-lg font-semibold">{{ number_format($usedUnits) }} / {{ number_format($plan->included_units) }} units</p>
                <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden mt-2">
                    <div class="h-full rounded-full {{ $percentage >= 100 ? 'bg-amber-500' : 'bg-indigo-500' }}" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500 text-sm">Overage Units</p>
                <p class="text-lg font-semibold {{ $overageUnits > 0 ? 'text-amber-600' : '' }}">{{ number_format($overageUnits) }}</p>
                <p class="text-xs text-gray-400 mt-1">at {{ \App\Support\Money::format($plan->overage_rate_cents, $plan->currency) }} / unit</p>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Invoices</h2>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Invoice</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('portal.invoices.show', $invoice) }}" class="text-indigo-600 hover:underline">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $invoice->period_start->format('d M') }} – {{ $invoice->period_end->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-700">{{ $invoice->status->value }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Money::format($invoice->total_amount_cents, $invoice->currency) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('portal.invoices.download', $invoice) }}" class="text-xs text-indigo-600 hover:underline">Download</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">No invoices yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.portal>
