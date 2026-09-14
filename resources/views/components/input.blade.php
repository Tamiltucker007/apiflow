@props(['label' => null, 'name', 'hint' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }}</label>
    @endif

    <input id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge([
        'autocomplete' => 'off',
        'class' => 'w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition',
    ]) }}>

    @if ($hint)
        <p class="text-xs text-gray-400 mt-1.5">{{ $hint }}</p>
    @endif
</div>
