<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Customer Portal' }} — APIFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-6">
        <div class="flex items-center gap-2">
            <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                <rect x="2" y="2" width="14" height="14" rx="4" class="fill-indigo-500"/>
                <rect x="16" y="16" width="14" height="14" rx="4" class="fill-violet-400"/>
            </svg>
            <span class="font-semibold text-gray-900">APIFlow</span>
            <span class="text-xs text-gray-400 border-l border-gray-200 pl-2 ml-1">Customer Portal</span>
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">{{ auth('customer')->user()->name }}</span>
            <form method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Log out</button>
            </form>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-6">
        @if (session('status'))
            <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
