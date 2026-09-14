@php
    // Middle plan of exactly 3 gets the "Most Popular" treatment — a
    // presentation choice only, not a flag on the Plan model itself.
    $popularIndex = $plans->count() === 3 ? 1 : null;
@endphp

<x-layouts.portal title="Choose a Plan">
    <div class="max-w-5xl">
        <h1 class="text-2xl font-semibold text-gray-900 mb-1">Choose your plan</h1>
        <p class="text-sm text-gray-500 mb-8">Pick the plan that fits your usage. You can switch anytime once you're subscribed.</p>

        @if ($errors->any())
            <x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <div class="grid sm:grid-cols-3 gap-6 items-start">
            @forelse ($plans as $index => $plan)
                @php($isPopular = $index === $popularIndex)
                <div class="relative bg-white rounded-2xl border p-6 flex flex-col transition hover:shadow-lg hover:-translate-y-0.5
                    {{ $isPopular ? 'border-indigo-500 shadow-lg sm:scale-[1.03]' : 'border-gray-100 shadow-sm' }}">
                    @if ($isPopular)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow whitespace-nowrap">
                            Most Popular
                        </span>
                    @endif

                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4 {{ $isPopular ? 'bg-indigo-600' : 'bg-indigo-50' }}">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M4 10.5l4 4 8-9" stroke="{{ $isPopular ? 'white' : '#4f46e5' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <h2 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h2>
                    <p class="mt-2">
                        <span class="text-3xl font-bold text-gray-900 whitespace-nowrap">{{ \App\Support\Money::format($plan->base_price_cents, $plan->currency) }}</span>
                        <span class="block text-sm text-gray-500 mt-0.5">per {{ $plan->billing_cycle->value }}</span>
                    </p>

                    <ul class="text-sm text-gray-600 mt-5 space-y-3 flex-1">
                        <li class="flex items-start gap-2">
                            <svg class="flex-shrink-0 mt-0.5 text-emerald-500" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8.2 6.5 12 13 4.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>{{ number_format($plan->included_units) }} units included</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="flex-shrink-0 mt-0.5 text-emerald-500" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8.2 6.5 12 13 4.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>{{ \App\Support\Money::format($plan->overage_rate_cents, $plan->currency) }} / unit after that</span>
                        </li>
                    </ul>

                    <form method="POST" action="{{ route('plans.choose.store') }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="w-full text-white text-sm font-medium py-2.5 rounded-lg transition
                            {{ $isPopular ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-900 hover:bg-gray-800' }}">
                            Subscribe to {{ $plan->name }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-lg shadow p-6 text-sm text-gray-500">
                    No plans are available to subscribe to right now.
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.portal>
