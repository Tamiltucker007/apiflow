<x-layouts.portal title="Profile">
    <h1 class="text-xl font-semibold text-gray-900 mb-1">Profile & Account</h1>
    <p class="text-sm text-gray-500 mb-6">Your account details and login settings.</p>

    @if ($errors->any())
        <x-alert class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Account Details</h2>
            <dl class="text-sm divide-y divide-gray-100">
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Name</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->name }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Email</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->email }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Company</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->merchant->name }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Account Status</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $customer->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Member Since</dt>
                    <dd class="font-medium text-gray-900">{{ $customer->created_at->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Change Password</h2>

            <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-input label="Current Password" name="current_password" type="password" />
                <x-input label="New Password" name="password" type="password" hint="At least 8 characters." />
                <x-input label="Confirm New Password" name="password_confirmation" type="password" />

                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</x-layouts.portal>
