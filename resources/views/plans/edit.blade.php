<x-layouts.app title="Edit Plan">
    <h1 class="text-xl font-semibold mb-6">Edit Plan</h1>

    <div class="bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <x-alert class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('admin.plans.update', [$merchant, $plan]) }}">
            @csrf
            @method('PUT')

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Name" name="name" value="{{ old('name', $plan->name) }}" required />

                <x-select label="Billing Cycle" name="billing_cycle">
                    @foreach (['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('billing_cycle', $plan->billing_cycle->value) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>

                <x-input label="Base Price (cents)" name="base_price_cents" type="number" value="{{ old('base_price_cents', $plan->base_price_cents) }}" min="0" required
                    hint="e.g. 499900 = Rs 4,999.00" />

                <x-input label="Included Units" name="included_units" type="number" value="{{ old('included_units', $plan->included_units) }}" min="0" required
                    hint="1 unit = 1 API call" />

                <x-input label="Overage Rate (cents / unit)" name="overage_rate_cents" type="number" value="{{ old('overage_rate_cents', $plan->overage_rate_cents) }}" min="0" required />

                <x-input label="Max Subscribers (optional)" name="max_subscribers" type="number" value="{{ old('max_subscribers', $plan->max_subscribers) }}" min="1"
                    hint="Leave blank for unlimited. Currently {{ $plan->activeSubscriptionsCount() }} active." />
            </div>

            <p class="text-xs text-gray-400 mt-4">
                Changes only apply going forward — invoices already generated keep the price and terms they were billed at.
            </p>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition">
                    Save Changes
                </button>
                <a href="{{ route('admin.plans.index', $merchant) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
