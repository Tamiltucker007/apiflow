<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'APIFlow' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen flex">
        @auth
            @php
                $user = auth()->user();
            @endphp
            <aside class="w-60 bg-gray-900 text-gray-300 flex-shrink-0 flex flex-col">
                <div class="flex items-center gap-2 px-4 py-4">
                    <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                        <rect x="2" y="2" width="14" height="14" rx="4" class="fill-indigo-500"/>
                        <rect x="16" y="16" width="14" height="14" rx="4" class="fill-violet-400"/>
                    </svg>
                    <span class="text-white font-semibold">APIFlow</span>
                </div>

                @php
                    $navIcon = fn (string $path) => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" class="flex-shrink-0"><path d="'.$path.'" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    $navLink = function (string $routePattern, string $href, string $label, string $iconPath) use ($navIcon) {
                        $active = request()->routeIs($routePattern);
                        $classes = $active
                            ? 'flex items-center gap-2.5 mx-2 px-3 py-2 rounded-lg text-white bg-indigo-500 font-medium shadow-sm'
                            : 'flex items-center gap-2.5 mx-2 px-3 py-2 rounded-lg text-gray-400 hover:bg-white/5 hover:text-white transition';

                        return '<a href="'.$href.'" class="'.$classes.'">'.$navIcon($iconPath).'<span>'.$label.'</span></a>';
                    };
                @endphp

                <nav class="mt-2 flex flex-col gap-0.5 text-sm flex-1">
                    @php($merchantId = $user->merchant_id)
                    {!! $navLink('merchants.dashboard', route('merchants.dashboard', $merchantId), 'Dashboard', 'M3 8.5 10 3l7 5.5V17a1 1 0 0 1-1 1h-4v-5H8v5H4a1 1 0 0 1-1-1V8.5Z') !!}
                    {!! $navLink('merchants.plans.*', route('merchants.plans.index', $merchantId), 'Plans', 'M4 4h6l7 7-6.5 6.5-7-7V4Z M7.5 7.5h.01') !!}
                    {!! $navLink('merchants.customers.*', route('merchants.customers.index', $merchantId), 'Customers', 'M13 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 17c0-2.8 2.7-5 6-5s6 2.2 6 5') !!}
                    {!! $navLink('merchants.subscriptions.*', route('merchants.subscriptions.index', $merchantId), 'Subscriptions', 'M4 4v4h4M16 16v-4h-4M4.5 10a5.5 5.5 0 0 1 9.4-3.9L16 8M15.5 10a5.5 5.5 0 0 1-9.4 3.9L4 12') !!}
                    {!! $navLink('merchants.invoices.*', route('merchants.invoices.index', $merchantId), 'Invoices', 'M6 3h8l2 2v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1ZM7.5 8h5M7.5 11h5M7.5 14h3') !!}
                    @if ($user->role !== \App\Enums\UserRole::MerchantStaff)
                        {!! $navLink('merchants.users.*', route('merchants.users.index', $merchantId), 'Team', 'M13 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 17c0-2.5 2.5-4.5 6-4.5s6 2 6 4.5M15 4.5a3 3 0 0 1 0 5.8M17.5 17c0-2-1.3-3.7-3.3-4.3') !!}
                    @endif
                </nav>

                <div class="border-t border-white/10 px-4 py-3">
                    <p class="text-gray-500 text-xs">FinPay Technologies</p>
                </div>
            </aside>
        @endauth

        <div class="flex-1 flex flex-col min-w-0">
            @auth
                <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-6 flex-shrink-0">
                    <div class="text-sm font-medium text-gray-700">
                        {{ $title ?? 'APIFlow' }}
                    </div>

                    <details class="relative">
                        <summary class="list-none flex items-center gap-2.5 cursor-pointer select-none">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <div class="w-7 h-7 rounded-full bg-indigo-500 text-white text-xs font-semibold flex items-center justify-center flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="text-left leading-tight">
                                <p class="text-xs font-medium text-gray-700">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $user->role->label() }}</p>
                            </div>
                        </summary>

                        <div class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-10">
                            <div class="px-3 py-2 border-b border-gray-100">
                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                    Log out
                                </button>
                            </form>
                        </div>
                    </details>
                </header>
            @endauth

            <main class="flex-1 p-6 overflow-y-auto">
                @if (session('status'))
                    <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
                @endif

                @if (session('error'))
                    <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @auth
        <x-confirm-modal />
    @endauth

    @stack('scripts')
</body>
</html>
