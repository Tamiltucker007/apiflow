<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'APIFlow' }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen flex">
        @auth
        <aside class="w-56 bg-gray-900 text-gray-200 flex-shrink-0">
            <div class="px-4 py-4 text-lg font-semibold text-white">APIFlow</div>
            <nav class="mt-2 flex flex-col text-sm">
                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 hover:bg-gray-800">All Merchants</a>
                @else
                    @php($merchantId = auth()->user()->merchant_id)
                    <a href="{{ route('merchants.dashboard', $merchantId) }}" class="px-4 py-2 hover:bg-gray-800">Dashboard</a>
                    <a href="{{ route('merchants.plans.index', $merchantId) }}" class="px-4 py-2 hover:bg-gray-800">Plans</a>
                    <a href="{{ route('merchants.customers.index', $merchantId) }}" class="px-4 py-2 hover:bg-gray-800">Customers</a>
                    <a href="{{ route('merchants.subscriptions.index', $merchantId) }}" class="px-4 py-2 hover:bg-gray-800">Subscriptions</a>
                @endif
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="px-4 py-4">
                @csrf
                <button type="submit" class="text-sm text-gray-400 hover:text-white">Log out</button>
            </form>
        </aside>
        @endauth

        <main class="flex-1 p-6">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2 text-sm">{{ session('status') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
