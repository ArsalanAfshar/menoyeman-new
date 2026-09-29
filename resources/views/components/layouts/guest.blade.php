{{-- Guest layout: login, auth and public pages. Persian, RTL, orange brand. --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'منوی من')</title>
    <meta name="description" content="@yield('description', 'منوی من — ساخت منوی دیجیتال برای رستوران، کافه و فست‌فود')">

    {{-- Brand icons (generated from the owner's logo) --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#F15A22">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('title', 'منوی من')">
    <meta property="og:description" content="@yield('description', 'منوی من — ساخت منوی دیجیتال')">
    <meta property="og:image" content="/og-image.png">
    <meta property="og:locale" content="fa_IR">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50">
    {{-- Subtle brand shapes — warm orange, never busy --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-brand-100 blur-3xl opacity-60"></div>
        <div class="absolute top-1/3 -right-32 h-[28rem] w-[28rem] rounded-full bg-brand-200 blur-3xl opacity-40"></div>
    </div>

    <div class="relative flex min-h-screen flex-col">
        <header class="flex items-center justify-between px-6 py-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="منوی من">
                <img src="/brand/logo-96.png" alt="منوی من" class="h-12 w-auto" width="96" height="96" loading="eager">
            </a>
            @yield('header-actions')
        </header>

        <main class="flex flex-1 items-center justify-center px-4 pb-16">
            <div class="w-full max-w-md">
                @yield('content')
            </div>
        </main>

        <footer class="px-6 py-5 text-center text-sm text-ink-500">
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
                <a href="{{ route('terms') }}" class="hover:text-brand-700">شرایط استفاده</a>
                <span class="text-ink-300" aria-hidden="true">|</span>
                <a href="{{ route('privacy') }}" class="hover:text-brand-700">حریم خصوصی</a>
                <span class="text-ink-300" aria-hidden="true">|</span>
                <span>پشتیبانی: <span class="fa-digits">۰۲۱-۱۲۳۴۵۶۷۸</span></span>
            </div>
            <p class="mt-2 text-xs text-ink-400">
                تمامی حقوق مادی و معنوی برای «منوی من» محفوظ است.
                <span class="fa-digits">{{ \App\Support\JalaliDate::date() }}</span>
            </p>
        </footer>
    </div>
</body>
</html>
