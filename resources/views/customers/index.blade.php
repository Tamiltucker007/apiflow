@php($canManage = auth()->user()->role !== \App\Enums\UserRole::MerchantStaff)

<x-layouts.app title="Customers">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Customers</h1>
        @if ($canManage)
            <a href="{{ route('merchants.customers.create', $merchant) }}"
                class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">+ Register Customer</a>
        @endif
    </div>

    @if ($errors->any())
        <x-alert class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="bg-white rounded-lg shadow overflow-x-auto pt-4 pb-4">
        <table id="customers-table" class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">External ID</th>
                    <th class="px-4 py-3">Registered</th>
                    @if ($canManage)
                        <th class="px-4 py-3 text-right">Actions</th>
                    @endif
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
                    { data: 'name_link', className: 'px-4 py-3 font-medium' },
                    { data: 'email', className: 'px-4 py-3' },
                    { data: 'external_id_fmt', className: 'px-4 py-3 text-gray-500', searchable: false, orderable: false },
                    { data: 'registered', className: 'px-4 py-3 text-gray-500', searchable: false, orderable: false },
                    @if ($canManage)
                        { data: 'actions', className: 'px-4 py-3 text-right', searchable: false, orderable: false },
                    @endif
                ];

                window.initDataTable('#customers-table', '{{ route('merchants.customers.data', $merchant) }}', columns, [[1, 'asc']]);
            });
        </script>
    @endpush
</x-layouts.app>
