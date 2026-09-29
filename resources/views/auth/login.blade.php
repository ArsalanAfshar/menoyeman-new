<x-layouts.guest>
    @section('title', 'ورود | منوی من')
    @section('description', 'ورود به پنل مدیریت منوی دیجیتال منوی من')

    @section('header-actions')
        <a href="{{ route('login.password') }}" class="btn-ghost compact px-3 py-1.5 text-sm">ورود با رمز عبور</a>
    @endsection

    @section('content')
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900">ورود به منوی من</h1>
            <p class="mt-2 text-ink-600">
                شماره موبایل خود را وارد کنید تا کد تایید برایتان ارسال شود.
            </p>

            @if ($errors->any())
                <div class="mt-4">
                    <x-alert type="error">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                </div>
            @endif

            <form method="POST" action="{{ route('login.otp.send') }}" class="mt-6 space-y-4" novalidate>
                @csrf

                {{-- Honeypot: invisible to humans, tempting for bots --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off"
                       aria-hidden="true" class="hidden">

                <div>
                    <label for="phone" class="label">شماره موبایل</label>
                    <input id="phone" name="phone" type="text" inputmode="numeric"
                           class="input fa-digits text-left" dir="ltr"
                           placeholder="۰۹۱۲۳۴۵۶۷۸۹"
                           value="{{ old('phone') }}"
                           x-data
                           x-on:input="$el.value = MenoyeMan.toEnglishDigits($el.value).replace(/[^0-9+]/g, '').slice(0, 15)"
                           required autofocus>
                    <p class="mt-1.5 text-xs text-ink-500">
                        با هر شماره‌ای می‌توانید وارد شوید؛ از ایران یا خارج (۹۸+).
                    </p>
                </div>

                <button type="submit" class="btn-primary w-full">
                    دریافت کد تایید
                </button>
            </form>

            <div class="mt-6 border-t border-ink-100 pt-5 text-center text-sm text-ink-600">
                تازه وارد هستید؟ با وارد کردن شماره، حساب شما به‌صورت خودکار ساخته می‌شود.
            </div>
        </div>
    @endsection
</x-layouts.guest>
