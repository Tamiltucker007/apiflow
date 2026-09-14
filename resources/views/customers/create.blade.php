<x-layouts.app title="Register Customer">
    <h1 class="text-xl font-semibold mb-6">Register Customer</h1>

    <div class="bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('merchants.customers.store', $merchant) }}">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Name" name="name" value="{{ old('name') }}" required />

                <x-input label="Email" name="email" type="email" value="{{ old('email') }}" required />

                <x-input label="External ID (optional)" name="external_id" value="{{ old('external_id') }}" />
            </div>

            <button type="submit" class="mt-6 bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition">
                Register Customer
            </button>
        </form>
    </div>
</x-layouts.app>
