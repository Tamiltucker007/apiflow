<x-layouts.app title="New User">
    <h1 class="text-xl font-semibold mb-6">New User</h1>

    <div class="bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('merchants.users.store', $merchant) }}">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Name" name="name" value="{{ old('name') }}" required />

                <x-input label="Email" name="email" type="email" value="{{ old('email') }}" required />

                <x-select label="Role" name="role">
                    <option value="merchant_staff" @selected(old('role', 'merchant_staff') === 'merchant_staff')>Merchant Staff (read-only)</option>
                    <option value="merchant_admin" @selected(old('role') === 'merchant_admin')>Merchant Admin (full access)</option>
                </x-select>

                <x-input label="Password" name="password" type="password" required
                    hint="At least 8 characters." />
            </div>

            <button type="submit" class="mt-6 bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition">
                Create User
            </button>
        </form>
    </div>
</x-layouts.app>
