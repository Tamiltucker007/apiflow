@props(['label' => null, 'name'])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }}</label>
    @endif

    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge([
        'class' => 'w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition',
    ]) }}>
        {{ $slot }}
    </select>
</div>
