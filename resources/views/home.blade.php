<x-layouts.guest>
    @section('title', 'منوی من | ساخت منوی دیجیتال رستوران، کافه و فست‌فود')
    @section('description', 'با منوی من، منوی دیجیتال کسب‌وکار خود را در چند دقیقه بسازید، کد QR چاپ کنید و سفارش مشتریان را آنلاین دریافت کنید.')

    @section('content')
        <div class="text-center">
            <h1 class="text-4xl font-black leading-tight text-ink-900 sm:text-5xl">
                منوی کسب‌وکار خود را
                <span class="text-brand-600">دیجیتال</span>
                کنید
            </h1>
            <p class="mx-auto mt-5 max-w-xl text-lg text-ink-600">
                با «منوی من» در چند دقیقه منوی خود را بسازید، کد QR چاپ کنید و سفارش مشتریان را بدون پرداخت آنلاین دریافت کنید.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('login') }}" class="btn-primary px-8 text-base">شروع رایگان</a>
                <a href="{{ route('login') }}" class="btn-secondary px-8 text-base">ورود به پنل</a>
            </div>

            <p class="mt-6 text-sm text-ink-500">
                ۱۰ روز استفاده رایگان — بدون نیاز به کارت بانکی
            </p>

            {{-- Placeholder for the phone mockup (full hero in Phase 8) --}}
            <div class="mx-auto mt-12 max-w-sm rounded-[2.5rem] border-8 border-ink-900 bg-white p-4 shadow-lift">
                <div class="rounded-[2rem] bg-brand-50 p-6 text-right">
                    <div class="mx-auto mb-4 h-2.5 w-24 rounded-full bg-ink-900/80"></div>
                    <p class="text-lg font-bold text-ink-900">کافه الماس ☕</p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between rounded-xl bg-white p-3 shadow-soft">
                            <span class="text-sm font-medium text-ink-800">اسپرسو دوبل</span>
                            <span class="text-sm font-bold text-brand-700 fa-digits">۸۵٬۰۰۰ تومان</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-white p-3 shadow-soft">
                            <span class="text-sm font-medium text-ink-800">لاته</span>
                            <span class="text-sm font-bold text-brand-700 fa-digits">۹۵٬۰۰۰ تومان</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-white p-3 shadow-soft">
                            <span class="text-sm font-medium text-ink-800">چای کیسه‌ای</span>
                            <span class="text-sm font-bold text-brand-700 fa-digits">۴۵٬۰۰۰ تومان</span>
                        </div>
                    </div>
                    <div class="mt-5 rounded-xl bg-brand-600 py-2.5 text-center text-sm font-semibold text-white">
                        ثبت سفارش
                    </div>
                </div>
            </div>
        </div>
    @endsection
</x-layouts.guest>
