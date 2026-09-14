<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — APIFlow</title>
    @vite('resources/css/app.css')
    <style>
        /* Neutralize the browser's autofill tint so fields stay white. */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: #111827;
            transition: background-color 9999s ease-in-out 0s;
        }
    </style>
</head>
<body class="bg-white">
    <div class="min-h-screen grid lg:grid-cols-2">
        <div class="flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-20">
            <div class="w-full max-w-sm mx-auto">
                <div class="flex items-center gap-2 mb-10">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect x="2" y="2" width="14" height="14" rx="4" class="fill-indigo-600"/>
                        <rect x="16" y="16" width="14" height="14" rx="4" class="fill-violet-500"/>
                    </svg>
                    <span class="text-lg font-semibold text-gray-900">APIFlow</span>
                </div>

                <h1 class="text-2xl font-semibold text-gray-900">Welcome back!</h1>
                <p class="text-sm text-gray-500 mt-1 mb-8">Sign in to manage your usage and billing.</p>

                <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5" novalidate>
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="18" height="18" viewBox="0 0 20 20" fill="none">
                                <path d="M3 5.5A1.5 1.5 0 0 1 4.5 4h11A1.5 1.5 0 0 1 17 5.5v9a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 3 14.5v-9Z" stroke="currentColor" stroke-width="1.4"/>
                                <path d="m4 5.5 6 5 6-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" autofocus autocomplete="email"
                                class="w-full rounded-lg border bg-white pl-10 pr-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm outline-none transition focus:ring-4 @error('email') border-red-400 focus:ring-red-100 focus:border-red-400 @else border-gray-300 focus:ring-indigo-100 focus:border-indigo-500 @enderror">
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                        </div>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="18" height="18" viewBox="0 0 20 20" fill="none">
                                <rect x="4.5" y="9" width="11" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                                <path d="M6.5 9V6.5a3.5 3.5 0 0 1 7 0V9" stroke="currentColor" stroke-width="1.4"/>
                            </svg>
                            <input type="password" name="password" placeholder="Enter your password" autocomplete="current-password"
                                class="w-full rounded-lg border bg-white pl-10 pr-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm outline-none transition focus:ring-4 @error('password') border-red-400 focus:ring-red-100 focus:border-red-400 @else border-gray-300 focus:ring-indigo-100 focus:border-indigo-500 @enderror">
                        </div>
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
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="hidden lg:flex relative bg-gradient-to-br from-indigo-700 via-violet-700 to-fuchsia-600 items-center justify-center overflow-hidden p-12">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 w-96 h-96 rounded-full bg-fuchsia-400/20 blur-3xl"></div>
            <svg class="absolute inset-0 w-full h-full opacity-10" viewBox="0 0 400 400" fill="none">
                <circle cx="60" cy="60" r="120" stroke="white" stroke-width="1"/>
                <circle cx="340" cy="340" r="160" stroke="white" stroke-width="1"/>
                <circle cx="200" cy="200" r="90" stroke="white" stroke-width="1"/>
            </svg>

            <div class="relative z-10 w-full max-w-md">
                <div class="rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <span class="text-xs font-medium text-white/70 uppercase tracking-wider">Merchant Dashboard</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-400/20 text-emerald-300">Live</span>
                    </div>

                    <div class="mb-5">
                        <div class="flex items-baseline justify-between mb-1.5">
                            <span class="text-white text-2xl font-semibold">184,320</span>
                            <span class="text-white/60 text-xs">/ 250,000 units</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-white/15 overflow-hidden">
                            <div class="h-full w-[74%] rounded-full bg-gradient-to-r from-emerald-400 to-teal-300"></div>
                        </div>
                    </div>

                    <div class="flex items-end gap-1.5 h-16 mb-5">
                        @foreach ([40, 65, 50, 80, 60, 90, 70] as $bar)
                            <div class="flex-1 rounded-t bg-white/40" style="height: {{ $bar }}%"></div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between text-sm border-t border-white/10 pt-4">
                        <span class="text-white/70">Top customer</span>
                        <span class="text-white font-medium">Beta Retail — 91%</span>
                    </div>
                </div>

                <div class="mt-10 text-center">
                    <h2 class="text-white text-2xl font-semibold mb-3">Meter usage. Bill accurately.</h2>
                    <p class="text-indigo-100/90 text-sm leading-relaxed mb-6">
                        Track every API call, aggregate usage per cycle, and generate accurate invoices with proration — all from one dashboard.
                    </p>

                    <ul class="inline-flex flex-col gap-2 text-left">
                        @foreach (['Multi-tenant architecture', 'Real-time usage metering', 'Automated prorated billing'] as $feature)
                            <li class="flex items-center gap-2 text-sm text-white/90">
                                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <circle cx="8" cy="8" r="8" class="fill-emerald-400/30"/>
                                    <path d="M5 8.2 7 10l4-4.5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
