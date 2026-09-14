<x-layouts.portal title="Invoices">
    <h1 class="text-xl font-semibold text-gray-900 mb-1">Invoices</h1>
    <p class="text-sm text-gray-500 mb-6">Every invoice issued to your account, most recent first.</p>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3">Invoice</th>
                    <th class="px-5 py-3">Period</th>
                    <th class="px-5 py-3">Plan</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Amount</th>
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
                        <td class="px-5 py-3 text-gray-500">{{ $invoice->subscription?->plan?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <x-portal.invoice-status :status="$invoice->status" />
                        </td>
                        <td class="px-5 py-3 text-right font-medium text-gray-900">{{ \App\Support\Money::format($invoice->total_amount_cents, $invoice->currency) }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('invoices.download', $invoice) }}" class="text-xs hover:underline" style="color: var(--brand-from)">Download</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-14 text-center text-gray-400">
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
