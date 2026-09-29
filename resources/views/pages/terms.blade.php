<x-layouts.guest>
    @section('title', 'شرایط استفاده | منوی من')
    @section('content')
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900">شرایط استفاده</h1>
            <div class="prose-sm mt-4 space-y-4 text-ink-600 leading-8">
                <p>این صفحه به‌زودی با متن کامل شرایط استفاده از خدمات «منوی من» تکمیل می‌شود.</p>
                <p>خلاصه: استفاده از منوی من به معنای پذیرش قوانین جمهوری اسلامی ایران در حوزه تجارت الکترونیک و حریم خصوصی کاربران است.</p>
            </div>
            <p class="mt-6 text-xs text-ink-400">آخرین به‌روزرسانی: <span class="fa-digits">{{ \App\Support\JalaliDate::date() }}</span></p>
        </div>
    @endsection
</x-layouts.guest>
