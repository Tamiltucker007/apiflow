<x-layouts.portal :title="$invoice->invoice_number">
    <a href="{{ route('portal.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Dashboard</a>

    <div class="flex items-center justify-between mt-2 mb-6">
        <div>
            <h1 class="text-xl font-semibold">{{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-gray-500">
                {{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-xs bg-amber-100 text-amber-700">{{ $invoice->status->value }}</span>
            <a href="{{ route('portal.invoices.download', $invoice) }}"
                class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">Download PDF</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($invoice->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            @if ($item->period_start)
                                {{ $item->period_start->format('d M') }} – {{ $item->period_end->format('d M') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Money::format($item->amount_cents, $invoice->currency) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 font-semibold">
                    <td colspan="2" class="px-4 py-3 text-right">Total</td>
                    <td class="px-4 py-3 text-right">{{ \App\Support\Money::format($invoice->total_amount_cents, $invoice->currency) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-layouts.portal>
