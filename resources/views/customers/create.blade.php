<x-layouts.app title="Register Customer">
    <h1 class="text-xl font-semibold mb-6">Register Customer</h1>

    <div class="bg-white rounded-lg shadow p-6 max-w-lg">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('merchants.customers.store', $merchant) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">External ID <span class="text-gray-400">(optional)</span></label>
                <input type="text" name="external_id" value="{{ old('external_id') }}"
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">
                Register Customer
            </button>
        </form>
    </div>
</x-layouts.app>
