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

    @php
        $navIcon = fn (string $path) => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" class="flex-shrink-0"><path d="'.$path.'" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $portalNavLink = function (string $routePattern, string $href, string $label, string $iconPath) use ($navIcon) {
            $active = request()->routeIs($routePattern);
            $classes = $active
                ? 'flex items-center gap-1.5 text-sm font-medium whitespace-nowrap px-3.5 py-2 rounded-lg'
                : 'flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 whitespace-nowrap px-3.5 py-2 rounded-lg transition';
            $style = $active ? 'color: var(--brand-from); background: color-mix(in srgb, var(--brand-from) 10%, white)' : '';

            return '<a href="'.$href.'" class="'.$classes.'" style="'.$style.'">'.$navIcon($iconPath).'<span>'.$label.'</span></a>';
        };
    @endphp

    <header class="bg-white border-b border-gray-200 px-4 sm:px-6">
        <div class="h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="flex-shrink-0" width="26" height="26" viewBox="0 0 32 32" fill="none">
                    <rect x="2" y="2" width="14" height="14" rx="4" style="fill: var(--brand-from)"/>
                    <rect x="16" y="16" width="14" height="14" rx="4" style="fill: var(--brand-to)"/>
                </svg>
                <span class="font-semibold text-gray-900 hidden sm:inline">APIFlow</span>
                <span class="text-gray-300 hidden sm:inline">/</span>
                <span class="text-sm font-medium truncate" style="color: var(--brand-from)">{{ $portalMerchant->name }}</span>
            </div>

            <details class="relative flex-shrink-0">
                <summary class="list-none flex items-center gap-2 cursor-pointer select-none px-2 py-1.5 rounded-lg hover:bg-gray-50 transition">
                    <div class="w-8 h-8 rounded-full text-white text-xs font-semibold flex items-center justify-center flex-shrink-0"
                        style="background: linear-gradient(135deg, var(--brand-from), var(--brand-to))">
                        {{ strtoupper(substr($portalCustomer->name, 0, 1)) }}
                    </div>
                    <span class="text-sm text-gray-700 font-medium hidden sm:inline">{{ $portalCustomer->name }}</span>
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" class="text-gray-400 hidden sm:inline"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </summary>

                <div class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-10">
                    <div class="px-3 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-700 truncate">{{ $portalCustomer->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $portalCustomer->email }}</p>
                    </div>
                    <a href="{{ route('profile') }}" class="block px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Profile &amp; Account</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Log out</button>
                    </form>
                </div>
            </details>
        </div>

        <nav class="flex items-center gap-1 overflow-x-auto pb-2.5">
            {!! $portalNavLink('dashboard', route('dashboard'), 'Dashboard', 'M3 8.5 10 3l7 5.5V17a1 1 0 0 1-1 1h-4v-5H8v5H4a1 1 0 0 1-1-1V8.5Z') !!}
            {!! $portalNavLink('subscription', route('subscription'), 'Subscription', 'M4 4h6l7 7-6.5 6.5-7-7V4Z M7.5 7.5h.01') !!}
            {!! $portalNavLink('usage', route('usage'), 'Usage Details', 'M3 17V9M8 17V3M13 17v-6M18 17v-3') !!}
            {!! $portalNavLink('invoices.*', route('invoices.index'), 'Invoices', 'M6 3h8l2 2v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1ZM7.5 8h5M7.5 11h5M7.5 14h3') !!}
        </nav>
    </header>

    <main class="p-4 sm:p-6">
        @if (session('status'))
            <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        {{ $slot }}
    </main>

    <x-confirm-modal />

    @stack('scripts')
</body>
</html>
