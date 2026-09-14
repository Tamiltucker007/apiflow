<x-layouts.app title="Subscriptions">
    <h1 class="text-xl font-semibold mb-6">Subscriptions</h1>

    @if ($errors->any())
        <x-alert class="mb-6 max-w-xl">{{ $errors->first() }}</x-alert>
    @endif

    <div class="bg-white rounded-lg shadow overflow-x-auto pt-4 pb-4">
        <table id="subscriptions-table" class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Current Period</th>
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
                    { data: 'customer_name', className: 'px-4 py-3 font-medium' },
                    { data: 'plan_name', className: 'px-4 py-3' },
                    { data: 'status_badge', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'period', className: 'px-4 py-3 text-gray-500', searchable: false, orderable: false },
                    { data: 'actions', className: 'px-4 py-3 text-right', searchable: false, orderable: false },
                ];

                window.initDataTable('#subscriptions-table', '{{ route('admin.subscriptions.data', $merchant) }}', columns, [[1, 'asc']]);
            });
        </script>
    @endpush
</x-layouts.app>
