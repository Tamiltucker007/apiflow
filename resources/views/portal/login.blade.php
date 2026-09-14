<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Login — APIFlow</title>
    @vite('resources/css/app.css')
    <style>
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: #111827;
            transition: background-color 9999s ease-in-out 0s;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <div class="flex items-center gap-2 mb-10 justify-center">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <rect x="2" y="2" width="14" height="14" rx="4" class="fill-indigo-600"/>
                    <rect x="16" y="16" width="14" height="14" rx="4" class="fill-violet-500"/>
                </svg>
                <span class="text-lg font-semibold text-gray-900">APIFlow</span>
            </div>

            <h1 class="text-2xl font-semibold text-gray-900 text-center">Customer Portal</h1>
            <p class="text-sm text-gray-500 mt-1 mb-8 text-center">Sign in to view your usage and invoices.</p>

            @if (session('status'))
                <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
            @endif

            <form method="POST" action="{{ route('portal.login.store') }}" class="space-y-5" novalidate>
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" autofocus autocomplete="email"
                        class="w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm outline-none transition focus:ring-4 @error('email') border-red-400 focus:ring-red-100 focus:border-red-400 @else border-gray-300 focus:ring-indigo-100 focus:border-indigo-500 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" placeholder="Enter your password" autocomplete="current-password"
                        class="w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm outline-none transition focus:ring-4 @error('password') border-red-400 focus:ring-red-100 focus:border-red-400 @else border-gray-300 focus:ring-indigo-100 focus:border-indigo-500 @enderror">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="mr-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Remember me
                </label>

                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-indigo-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-indigo-700 transition">
                    Sign in
                </button>
            </form>

            <p class="text-xs text-gray-400 text-center mt-8">
                Don't have a password yet? Ask the merchant that set up your account to enable portal access.
            </p>
        </div>
    </div>
</body>
</html>
