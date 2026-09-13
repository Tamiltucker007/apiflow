@props(['type' => 'error'])

@php
    $styles = $type === 'success'
        ? 'bg-green-50 text-green-700'
        : 'bg-red-50 text-red-700';
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg text-sm px-4 py-2.5 {$styles}"]) }}>
    {{ $slot }}
</div>
