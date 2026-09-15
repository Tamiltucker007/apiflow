<x-layouts.app title="New Plan">
    <h1 class="text-xl font-semibold mb-6">New Plan</h1>

    <div class="bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('admin.plans.store', $merchant) }}">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Name" name="name" value="{{ old('name') }}" required />

                <x-select label="Billing Cycle" name="billing_cycle">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </x-select>

                <x-input label="Base Price (cents)" name="base_price_cents" type="number" value="{{ old('base_price_cents') }}" min="0" required
                    hint="e.g. 499900 = Rs 4,999.00" />

                <x-input label="Included Units" name="included_units" type="number" value="{{ old('included_units') }}" min="0" required
                    hint="1 unit = 1 API call" />

                <x-input label="Overage Rate (cents / unit)" name="overage_rate_cents" type="number" value="{{ old('overage_rate_cents') }}" min="0" required />

                <x-input label="Max Subscribers (optional)" name="max_subscribers" type="number" value="{{ old('max_subscribers') }}" min="1"
                    hint="Leave blank for unlimited" />
            </div>

            <button type="submit" class="mt-6 bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition">
                Create Plan
            </button>
        </form>
    </div>
</x-layouts.app>
