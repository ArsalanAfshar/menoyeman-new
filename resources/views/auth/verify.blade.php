<x-layouts.guest>
    @section('title', 'تایید شماره موبایل | منوی من')
    @section('content')
        <div class="card p-8"
             x-data="{
                 seconds: {{ $resendAfter }},
                 get busy() { return this.seconds > 0; },
                 tick() { if (this.seconds > 0) setTimeout(() => { this.seconds--; this.tick(); }, 1000); },
                 init() {
                     this.tick();
                     const input = this.$refs.code;
                     input.focus();
                 }
             }">
            <h1 class="text-2xl font-bold text-ink-900">تایید شماره موبایل</h1>
            <p class="mt-2 text-ink-600">
                کد تایید به شماره
                <span class="font-semibold fa-digits">{{ \App\Support\Phone::mask($phone) }}</span>
                ارسال شد.
            </p>

            @if (session('otp_resent'))
                <div class="mt-4"><x-alert type="success">کد جدید ارسال شد.</x-alert></div>
            @endif

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

            @if (! empty($fakeCode))
                <div class="mt-4">
                    <x-alert type="warning">
                        حالت آزمایشی — کد شما:
                        <span class="font-bold fa-digits">{{ \App\Support\Persian::digits($fakeCode) }}</span>
                    </x-alert>
                </div>
            @endif

            <form method="POST" action="{{ route('login.verify.submit') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="code" class="label">کد تایید</label>
                    <input id="code" name="code" type="text" inputmode="numeric"
                           maxlength="6" x-ref="code"
                           class="input fa-digits text-center text-2xl tracking-[0.5em]"
                           dir="ltr"
                           placeholder="------"
                           x-data
                           x-on:input="$el.value = MenoyeMan.toEnglishDigits($el.value).replace(/[^0-9]/g, '').slice(0, 6)"
                           autocomplete="one-time-code"
                           required>
                </div>

                <button type="submit" class="btn-primary w-full">ورود</button>
            </form>

            <div class="mt-6 flex flex-col items-center gap-3 border-t border-ink-100 pt-5 text-sm">
                <button type="button"
                        class="compact text-brand-700 font-medium disabled:text-ink-400 disabled:cursor-not-allowed"
                        :disabled="busy"
                        onclick="document.getElementById('resend-form').submit()">
                    <span x-show="! busy">ارسال مجدد کد</span>
                    <span x-show="busy" x-cloak>
                        ارسال مجدد کد تا <span class="fa-digits" x-text="MenoyeMan.toPersianDigits(String(this.seconds).padStart(2, '0'))"></span> ثانیه دیگر
                    </span>
                </button>

                <a href="{{ route('login') }}" class="compact text-ink-600 hover:text-brand-700">تغییر شماره موبایل</a>
            </div>

            <form id="resend-form" method="POST" action="{{ route('login.resend') }}" class="hidden">
                @csrf
            </form>
        </div>
    @endsection
</x-layouts.guest>
