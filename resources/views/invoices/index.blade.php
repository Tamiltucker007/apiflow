<x-layouts.app title="Invoices">
    <h1 class="text-xl font-semibold mb-6">Invoices</h1>

    <div class="bg-white rounded-lg shadow overflow-x-auto pt-4 pb-4">
        <table id="invoices-table" class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Invoice #</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y"></tbody>
        </table>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const columns = [
                    window.dtSerialColumn(),
                    { data: 'invoice_link', className: 'px-4 py-3 font-medium' },
                    { data: 'customer_name', className: 'px-4 py-3' },
                    { data: 'period', className: 'px-4 py-3 text-gray-500', searchable: false, orderable: false },
                    { data: 'total', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'status_badge', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'actions', className: 'px-4 py-3 text-right', searchable: false, orderable: false },
                ];

                window.initDataTable('#invoices-table', '{{ route('merchants.invoices.data', $merchant) }}', columns, [[1, 'desc']]);
            });
        </script>
    @endpush
</x-layouts.app>
