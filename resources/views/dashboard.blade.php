<x-layouts.app>
    <h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
    <p class="text-gray-600">Logged in as {{ auth()->user()->name }} ({{ auth()->user()->role->label() }}).</p>
    <p class="text-gray-400 text-sm mt-4">Usage analytics and billing summary coming soon.</p>
</x-layouts.app>
