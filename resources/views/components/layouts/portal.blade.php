@php
    $portalCustomer = auth('customer')->user();
    $portalMerchant = $portalCustomer->merchant;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Customer Portal' }} — APIFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Per-merchant brand color (see Merchant::themeFrom/themeTo) — every
           portal page a customer sees carries their own merchant's colors,
           not a generic default, the same mechanism as the register page. */
        :root {
            --brand-from: {{ $portalMerchant->themeFrom() }};
            --brand-to: {{ $portalMerchant->themeTo() }};
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="h-1" style="background: linear-gradient(90deg, var(--brand-from), var(--brand-to))"></div>

    <header class="min-h-14 bg-white border-b border-gray-200 flex items-center justify-between gap-3 px-4 sm:px-6 py-2">
        <div class="flex items-center gap-2 min-w-0">
            <svg class="flex-shrink-0" width="24" height="24" viewBox="0 0 32 32" fill="none">
                <rect x="2" y="2" width="14" height="14" rx="4" style="fill: var(--brand-from)"/>
                <rect x="16" y="16" width="14" height="14" rx="4" style="fill: var(--brand-to)"/>
            </svg>
            <span class="font-semibold text-gray-900 hidden sm:inline">APIFlow</span>
            <span class="text-gray-300 hidden sm:inline">/</span>
            <span class="text-sm font-medium truncate" style="color: var(--brand-from)">{{ $portalMerchant->name }}</span>
        </div>

        <div class="flex items-center gap-3 flex-shrink-0">
            <div class="w-7 h-7 rounded-full text-white text-xs font-semibold flex items-center justify-center flex-shrink-0"
                style="background: linear-gradient(135deg, var(--brand-from), var(--brand-to))">
                {{ strtoupper(substr($portalCustomer->name, 0, 1)) }}
            </div>
            <span class="text-sm text-gray-600 hidden sm:inline">{{ $portalCustomer->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">Log out</button>
            </form>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 sm:p-6">
        @if (session('status'))
            <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
