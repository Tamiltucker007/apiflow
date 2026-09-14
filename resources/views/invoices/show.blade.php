<x-layouts.app :title="$invoice->invoice_number">
    <a href="{{ route('merchants.invoices.index', $merchant) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Invoices</a>

    <div class="flex items-center justify-between mt-2 mb-6">
        <div>
            <h1 class="text-xl font-semibold">{{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-gray-500">
                {{ $invoice->customer->name }} &middot;
                {{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-xs bg-amber-100 text-amber-700">{{ $invoice->status->value }}</span>
            <a href="{{ route('merchants.invoices.download', [$merchant, $invoice]) }}"
                class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">Download PDF</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($invoice->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-gray-500 capitalize">{{ str_replace('_', ' ', $item->type->value) }}</td>
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
                    <td colspan="3" class="px-4 py-3 text-right">Total</td>
                    <td class="px-4 py-3 text-right">{{ \App\Support\Money::format($invoice->total_amount_cents, $invoice->currency) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="grid sm:grid-cols-3 gap-4 text-sm">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500">Base charges</p>
            <p class="text-lg font-semibold">{{ \App\Support\Money::format($invoice->base_amount_cents, $invoice->currency) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500">Overage ({{ number_format($invoice->overage_units) }} units)</p>
            <p class="text-lg font-semibold">{{ \App\Support\Money::format($invoice->overage_amount_cents, $invoice->currency) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500">Total</p>
            <p class="text-lg font-semibold">{{ \App\Support\Money::format($invoice->total_amount_cents, $invoice->currency) }}</p>
        </div>
    </div>
</x-layouts.app>
