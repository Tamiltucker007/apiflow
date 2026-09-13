<x-layouts.app title="Invoices">
    <h1 class="text-xl font-semibold mb-6">Invoices</h1>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Invoice #</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('merchants.invoices.show', [$merchant, $invoice]) }}" class="text-indigo-600 hover:underline">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $invoice->customer->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $invoice->period_start->format('d M') }} – {{ $invoice->period_end->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Money::format($invoice->total_amount_cents, $invoice->currency) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-700">
                                {{ $invoice->status->value }}
                            </span>
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
</x-layouts.app>
