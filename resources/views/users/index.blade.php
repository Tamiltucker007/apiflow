<x-layouts.app title="Team">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Team</h1>
        <a href="{{ route('admin.users.create', $merchant) }}"
            class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">+ New User</a>
    </div>

    @if ($errors->any())
        <x-alert class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="bg-white rounded-lg shadow overflow-x-auto pt-4 pb-4">
        <table id="users-table" class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
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
                    { data: 'name_display', className: 'px-4 py-3 font-medium' },
                    { data: 'email', className: 'px-4 py-3' },
                    { data: 'status_badge', className: 'px-4 py-3', searchable: false, orderable: false },
                    { data: 'actions', className: 'px-4 py-3 text-right', searchable: false, orderable: false },
                ];

                window.initDataTable('#users-table', '{{ route('admin.users.data', $merchant) }}', columns, [[1, 'asc']]);
            });
        </script>
    @endpush
</x-layouts.app>
