<x-layouts.guest>
    @section('title', 'ورود با رمز عبور | منوی من')
    @section('header-actions')
        <a href="{{ route('login') }}" class="btn-ghost compact px-3 py-1.5 text-sm">ورود با کد تایید</a>
    @endsection
    @section('content')
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900">ورود با رمز عبور</h1>
            <p class="mt-2 text-ink-600">شماره موبایل و رمز عبور خود را وارد کنید.</p>

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

            <form method="POST" action="{{ route('login.password.submit') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="phone" class="label">شماره موبایل</label>
                    <input id="phone" name="phone" type="text" inputmode="numeric"
                           class="input fa-digits text-left" dir="ltr"
                           placeholder="۰۹۱۲۳۴۵۶۷۸۹" value="{{ old('phone') }}"
                           x-data
                           x-on:input="$el.value = MenoyeMan.toEnglishDigits($el.value).replace(/[^0-9+]/g, '').slice(0, 15)"
                           required autofocus>
                </div>
                <div>
                    <label for="password" class="label">رمز عبور</label>
                    <input id="password" name="password" type="password" class="input" required>
                </div>

                <button type="submit" class="btn-primary w-full">ورود</button>
            </form>

            <div class="mt-6 border-t border-ink-100 pt-5 text-center text-sm">
                <form method="POST" action="{{ route('password.forgot') }}" class="space-y-3">
                    @csrf
                    <p class="text-ink-600">رمز عبور را فراموش کرده‌اید؟</p>
                    <div class="flex gap-2">
                        <input type="text" name="phone" inputmode="numeric"
                               class="input fa-digits text-left" dir="ltr"
                               placeholder="شماره موبایل"
                               x-data
                               x-on:input="$el.value = MenoyeMan.toEnglishDigits($el.value).replace(/[^0-9+]/g, '').slice(0, 15)">
                        <button type="submit" class="btn-secondary compact whitespace-nowrap">بازیابی رمز</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection
</x-layouts.guest>
