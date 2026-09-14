<x-layouts.app title="Edit User">
    <h1 class="text-xl font-semibold mb-6">Edit User</h1>

    <div class="bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('merchants.users.update', [$merchant, $targetUser]) }}">
            @csrf
            @method('PUT')

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Name" name="name" value="{{ old('name', $targetUser->name) }}" required />

                <x-input label="Email" name="email" type="email" value="{{ old('email', $targetUser->email) }}" required />

                <x-select label="Role" name="role">
                    <option value="merchant_staff" @selected(old('role', $targetUser->role->value) === 'merchant_staff')>Merchant Staff (read-only)</option>
                    <option value="merchant_admin" @selected(old('role', $targetUser->role->value) === 'merchant_admin')>Merchant Admin (full access)</option>
                </x-select>

                <x-input label="New Password (optional)" name="password" type="password"
                    hint="Leave blank to keep the current password." />
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition">
                    Save Changes
                </button>
                <a href="{{ route('merchants.users.index', $merchant) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
