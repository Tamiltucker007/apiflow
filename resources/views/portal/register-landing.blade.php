<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up — APIFlow</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col items-center px-6 py-16">
        <div class="w-full max-w-2xl">
            <div class="flex items-center gap-2 mb-10 justify-center">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <rect x="2" y="2" width="14" height="14" rx="4" class="fill-indigo-600"/>
                    <rect x="16" y="16" width="14" height="14" rx="4" class="fill-violet-500"/>
                </svg>
                <span class="text-lg font-semibold text-gray-900">APIFlow</span>
            </div>

            <h1 class="text-2xl font-semibold text-gray-900 text-center">Which business are you signing up with?</h1>
            <p class="text-sm text-gray-500 mt-1 mb-10 text-center">
                Pick the business whose API you use — you'll create your account and choose a plan with them next.
            </p>

            <div class="space-y-3">
                @forelse ($merchants as $merchant)
                    {{-- --brand is a CSS custom property set per-row from the merchant's own
                         color; the Tailwind arbitrary-value classes below reference it by name
                         (a static string Tailwind's compiler can see), so each row's hover
                         state resolves to a different color at runtime with no per-merchant
                         Tailwind classes ever needing to exist. --}}
                    <a href="{{ route('register.show', $merchant) }}" style="--brand: {{ $merchant->themeFrom() }}"
                        class="flex items-center gap-4 bg-white border border-gray-200 rounded-xl px-5 py-4 shadow-sm transition hover:shadow-md hover:[border-color:var(--brand)] group">
                        {{-- Brand color swatch — a quick visual preview of that merchant's theme before clicking through. --}}
                        <span class="flex-shrink-0 w-2.5 h-10 rounded-full"
                            style="background: linear-gradient(180deg, {{ $merchant->themeFrom() }}, {{ $merchant->themeTo() }})"></span>

                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">{{ $merchant->name }}</p>
                            @if ($merchant->description)
                                <p class="text-sm text-gray-500 mt-0.5">{{ $merchant->description }}</p>
                            @endif
                        </div>

                        <svg class="flex-shrink-0 text-gray-300 transition group-hover:[color:var(--brand)]" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @empty
                    <div class="bg-white rounded-xl shadow-sm p-6 text-center text-sm text-gray-500">
                        No businesses are accepting signups right now.
                    </div>
                @endforelse
            </div>

            <p class="text-xs text-gray-400 text-center mt-10">
                Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>
