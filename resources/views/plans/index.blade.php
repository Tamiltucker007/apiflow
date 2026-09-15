<x-layouts.app title="Plans">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Plans</h1>
        <a href="{{ route('admin.plans.create', $merchant) }}"
            class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">+ New Plan</a>
    </div>

    @if ($errors->any())
        <x-alert class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    @foreach ($nearLimitPlans as $plan)
        <x-alert type="warning" class="mb-3">
            <strong>{{ $plan->name }}</strong> is at {{ $plan->activeSubscriptionsCount() }} of {{ $plan->max_subscribers }} subscribers — nearing its limit.
        </x-alert>
    @endforeach

    <div class="bg-white rounded-lg shadow overflow-x-auto pt-4 pb-4">
        <table id="plans-table" class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Billing Cycle</th>
                    <th class="px-4 py-3">Base Price</th>
                    <th class="px-4 py-3" title="1 unit = 1 API call">Included Units</th>
                    <th class="px-4 py-3">Overage Rate</th>
                    <th class="px-4 py-3">Subscribers</th>
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
                    { data: 'name', className: 'px-4 py-3 font-medium' },
                    { data: 'billing_cycle_label', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'base_price', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'included_units_fmt', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'overage_rate', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'subscribers', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'status_badge', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'actions', className: 'px-4 py-3 text-right', searchable: false, orderable: false },
                ];

                window.initDataTable('#plans-table', '{{ route('admin.plans.data', $merchant) }}', columns, [[1, 'asc']]);
            });
        </script>
    @endpush
</x-layouts.app>
