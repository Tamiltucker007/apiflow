<x-layouts.portal title="Subscription">
    <h1 class="text-xl font-semibold text-gray-900 mb-1">Subscription</h1>
    <p class="text-sm text-gray-500 mb-6">Your current plan and billing details.</p>

    @if ($errors->any())
        <x-alert class="mb-6 max-w-3xl mx-auto">{{ $errors->first() }}</x-alert>
    @endif

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <dl class="text-sm divide-y divide-gray-100">
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Plan</dt>
                    <dd class="font-medium text-gray-900">{{ $plan->name }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Price</dt>
                    <dd class="font-medium text-gray-900">{{ \App\Support\Money::format($plan->base_price_cents, $plan->currency) }} / {{ $plan->billing_cycle->value }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Billing Interval</dt>
                    <dd class="font-medium text-gray-900 capitalize">{{ $plan->billing_cycle->value }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Status</dt>
                    <dd><span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 capitalize">{{ $subscription->status->value }}</span></dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Started</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->started_at->format('d M Y') }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Next Billing Date</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->current_period_end->format('d M Y') }}</dd>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <dt class="text-gray-500">Renewal</dt>
                    <dd class="font-medium text-gray-900">Auto-renews into a new {{ $plan->billing_cycle->value }} cycle</dd>
                </div>
            </dl>
        </div>

        @if ($otherPlans->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-1">Change Plan</h2>
                <p class="text-xs text-gray-500 mb-4">
                    Usage before today stays billed at your current plan's rate, usage after switches to the new plan's rate.
                </p>

                <form method="POST" action="{{ route('subscription.change-plan') }}" class="flex flex-wrap items-end gap-3"
                    data-confirm-template="Switch to {value}? This takes effect immediately."
                    data-confirm-title="Change Plan" data-confirm-action="Change Plan">
                    @csrf
                    @method('PUT')

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">New Plan</label>
                        <select name="plan_id" required class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition">
                            @foreach ($otherPlans as $otherPlan)
                                <option value="{{ $otherPlan->id }}">{{ $otherPlan->name }} — {{ \App\Support\Money::format($otherPlan->base_price_cents, $otherPlan->currency) }} / {{ $otherPlan->billing_cycle->value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-indigo-700 transition">
                        Change Plan
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-layouts.portal>
