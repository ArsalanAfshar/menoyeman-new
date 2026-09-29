{{-- Owner panel layout. Persian, RTL, orange brand, mobile-first. --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت | منوی من')</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#F15A22">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50" x-data="{ sidebarOpen: false }">

    <div class="flex min-h-screen">
        {{-- Sidebar (drawer on mobile) --}}
        <aside class="fixed inset-y-0 right-0 z-40 w-72 transform bg-white border-l border-ink-100 transition-transform lg:static lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0 shadow-lift' : 'translate-x-full lg:translate-x-0'"
               x-cloak>
            <div class="flex h-16 items-center justify-between border-b border-ink-100 px-5">
                <a href="{{ route('panel.dashboard') }}" class="flex items-center">
                    <img src="/brand/logo.svg" alt="منوی من" class="h-10 w-auto">
                </a>
                <button class="compact p-2 text-ink-500 lg:hidden" @click="sidebarOpen = false" aria-label="بستن منو">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

            <nav class="flex flex-col gap-1 p-4 text-sm">
                <a href="{{ route('panel.dashboard') }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium {{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-800' : 'text-ink-700 hover:bg-ink-50' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>
                    داشبورد
                </a>

                <a href="{{ route('panel.onboarding') }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium {{ request()->routeIs('panel.onboarding*') ? 'bg-brand-50 text-brand-800' : 'text-ink-700 hover:bg-ink-50' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                    اطلاعات کسب‌وکار
                </a>
            </nav>

            <div class="absolute bottom-0 w-full border-t border-ink-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-800">
                        {{ \App\Support\Persian::digits(mb_substr($user->name ?: $user->phone, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink-900">{{ $user->name ?: \App\Support\Phone::mask($user->phone) }}</p>
                        <p class="truncate text-xs text-ink-500 fa-digits">{{ \App\Support\Phone::mask($user->phone) }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="compact rounded-lg p-2 text-ink-500 hover:bg-ink-100 hover:text-danger-600" title="خروج">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Overlay for mobile drawer --}}
        <div class="fixed inset-0 z-30 bg-ink-900/40 lg:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

        {{-- Main column --}}
        <div class="flex min-h-screen w-full flex-col lg:mr-72">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-ink-100 bg-white/90 px-4 backdrop-blur lg:px-8">
                <button class="compact rounded-lg p-2 text-ink-700 lg:hidden" @click="sidebarOpen = true" aria-label="باز کردن منو">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <h1 class="text-lg font-bold text-ink-900">@yield('title', 'داشبورد')</h1>

                <a href="{{ $menu ? '/m/' . $menu->slug : '#' }}" target="_blank"
                   class="btn-secondary compact hidden px-4 py-2 text-sm sm:inline-flex {{ $menu ? '' : 'pointer-events-none opacity-40' }}">
                    مشاهده منوی من
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7"/><path d="M9 7h8v8"/></svg>
                </a>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-4">
                        <x-alert type="success">{{ session('status') }}</x-alert>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
