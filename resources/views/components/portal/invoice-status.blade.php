@props(['status'])

@php
    $styles = [
        'draft' => 'bg-gray-100 text-gray-600',
        'pending' => 'bg-amber-100 text-amber-700',
        'paid' => 'bg-emerald-100 text-emerald-700',
        'failed' => 'bg-red-100 text-red-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'px-2 py-0.5 rounded text-xs font-medium capitalize '.($styles[$status->value] ?? 'bg-gray-100 text-gray-600')]) }}>
    {{ $status->value }}
</span>
