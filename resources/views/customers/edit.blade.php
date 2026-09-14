<x-layouts.app title="Edit Customer">
    <h1 class="text-xl font-semibold mb-6">Edit Customer</h1>

    <div class="bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('merchants.customers.update', [$merchant, $customer]) }}">
            @csrf
            @method('PUT')

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Name" name="name" value="{{ old('name', $customer->name) }}" required />

                <x-input label="Email" name="email" type="email" value="{{ old('email', $customer->email) }}" required />

                <x-input label="Contact Number (optional)" name="phone" type="tel" value="{{ old('phone', $customer->phone) }}" />
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition">
                    Save Changes
                </button>
                <a href="{{ route('merchants.customers.index', $merchant) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
