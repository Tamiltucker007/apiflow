<x-layouts.app title="New Plan">
    <h1 class="text-xl font-semibold mb-6">New Plan</h1>

    <div class="bg-white rounded-lg shadow p-6 max-w-lg">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('merchants.plans.store', $merchant) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Billing Cycle</label>
                <select name="billing_cycle" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Base Price (cents)</label>
                <input type="number" name="base_price_cents" value="{{ old('base_price_cents') }}" min="0" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">e.g. 499900 = Rs 4,999.00</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Included Units</label>
                <input type="number" name="included_units" value="{{ old('included_units') }}" min="0" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Overage Rate (cents / unit)</label>
                <input type="number" name="overage_rate_cents" value="{{ old('overage_rate_cents') }}" min="0" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700">
                Create Plan
            </button>
        </form>
    </div>
</x-layouts.app>
