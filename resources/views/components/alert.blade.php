@props(['type' => 'error'])

@php
    $variants = [
        'success' => [
            'classes' => 'bg-green-50 border-green-500 text-green-800',
            'icon' => 'text-green-500',
            'path' => 'M8 12.5l2.5 2.5 5-6',
        ],
        'error' => [
            'classes' => 'bg-red-50 border-red-500 text-red-800',
            'icon' => 'text-red-500',
            'path' => 'M12 8v4.5M12 15.5h.01',
        ],
    ];
    $variant = $variants[$type] ?? $variants['error'];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border-l-4 px-4 py-3 shadow-sm {$variant['classes']}"]) }} role="alert">
    <svg class="flex-shrink-0 w-5 h-5 mt-px {{ $variant['icon'] }}" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/>
        <path d="{{ $variant['path'] }}" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>

    <p class="flex-1 text-sm font-medium leading-5">{{ $slot }}</p>

    <button type="button" onclick="this.closest('[role=alert]').remove()"
        class="flex-shrink-0 {{ $variant['icon'] }} opacity-60 hover:opacity-100 transition" aria-label="Dismiss">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
    </button>
</div>
