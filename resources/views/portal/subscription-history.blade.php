@php
    $statusStyles = [
        'active' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-red-100 text-red-700',
        'expired' => 'bg-gray-100 text-gray-500',
    ];
@endphp

<x-layouts.portal title="Subscription History">
    <h1 class="text-xl font-semibold text-gray-900 mb-1">Subscription History</h1>
    <p class="text-sm text-gray-500 mb-6">Every subscription and plan change on your account, most recent first.</p>

    <div class="max-w-3xl mx-auto space-y-4">
        @forelse ($subscriptions as $subscription)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-gray-900">{{ $subscription->plan->name }}</h2>
                        <span class="px-2 py-0.5 rounded text-xs font-medium capitalize {{ $statusStyles[$subscription->status->value] ?? 'bg-gray-100 text-gray-500' }}">
                            {{ $subscription->status->value }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $subscription->started_at->format('d M Y') }} – {{ $subscription->cancelled_at?->format('d M Y') ?? 'present' }}</span>
                </div>

                <dl class="text-sm divide-y divide-gray-100">
                    <div class="flex items-center justify-between py-2">
                        <dt class="text-gray-500">Current Period</dt>
                        <dd class="font-medium text-gray-900">{{ $subscription->current_period_start->format('d M Y') }} – {{ $subscription->current_period_end->format('d M Y') }}</dd>
                    </div>
                    @if ($subscription->cancelled_at)
                        <div class="flex items-center justify-between py-2">
                            <dt class="text-gray-500">Cancelled</dt>
                            <dd class="font-medium text-gray-900">{{ $subscription->cancelled_at->format('d M Y, h:i A') }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($subscription->planChanges->isNotEmpty())
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Plan Changes</p>
                        <ul class="text-sm text-gray-600 space-y-1.5">
                            @foreach ($subscription->planChanges as $change)
                                <li class="flex items-center gap-1.5">
                                    <span class="text-gray-400">{{ $change->effective_date->format('d M Y') }}:</span>
                                    <span>{{ $change->oldPlan->name }} &rarr; {{ $change->newPlan->name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
                No subscription history yet.
            </div>
        @endforelse
    </div>
</x-layouts.portal>
