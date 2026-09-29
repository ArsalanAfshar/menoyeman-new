<x-layouts.guest>
    @section('title', 'حریم خصوصی | منوی من')
    @section('content')
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900">حریم خصوصی</h1>
            <div class="prose-sm mt-4 space-y-4 text-ink-600 leading-8">
                <p>اطلاعات شما نزد «منوی من» محفوظ است و در اختیار شخص ثالث قرار نمی‌گیرد.</p>
                <p>شماره موبایل شما صرفاً برای ورود، اطلاع‌رسانی سفارش‌ها و پشتیبانی استفاده می‌شود.</p>
            </div>
            <p class="mt-6 text-xs text-ink-400">آخرین به‌روزرسانی: <span class="fa-digits">{{ \App\Support\JalaliDate::date() }}</span></p>
        </div>
    @endsection
</x-layouts.guest>
