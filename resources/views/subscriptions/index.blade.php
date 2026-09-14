<x-layouts.app title="Subscriptions">
    <h1 class="text-xl font-semibold mb-6">Subscriptions</h1>

    @if ($errors->any())
        <x-alert class="mb-6 max-w-xl">{{ $errors->first() }}</x-alert>
    @endif

    <div class="bg-white rounded-lg shadow p-6 max-w-xl mb-8">
        <h2 class="text-sm font-medium text-gray-700 mb-4">Subscribe a customer to a plan</h2>

        <form method="POST" action="{{ route('admin.subscriptions.store', $merchant) }}" class="flex flex-wrap items-end gap-4">
            @csrf

            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700">Customer</label>
                <select name="customer_id" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700">Plan</label>
                <select name="plan_id" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} ({{ \App\Support\Money::format($plan->base_price_cents, $plan->currency) }})</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">
                Subscribe
            </button>
        </form>
    </div>

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
