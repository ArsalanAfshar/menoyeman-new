<x-layouts.app :user="$user" :menu="$menu">
    @section('title', $menu ? 'اطلاعات کسب‌وکار' : 'ساخت منوی من')

    @section('content')
        <div class="mx-auto max-w-2xl"
             x-data="{
                 step: {{ $menu ? 2 : 0 }},
                 name: @js(old('name', $menu->name ?? '')),
                 businessType: @js(old('business_type', $menu->business_type ?? '')),
                 slug: @js(old('slug', $menu->slug ?? '')),
                 slugState: 'idle', // idle | checking | ok | error
                 slugMessage: '',
                 slugTimer: null,
                 checkSlug() {
                     clearTimeout(this.slugTimer);
                     const v = this.slug.toLowerCase();
                     if (v.length < 3) { this.slugState = 'idle'; this.slugMessage = ''; return; }
                     this.slugState = 'checking';
                     this.slugTimer = setTimeout(async () => {
                         try {
                             const res = await fetch(@json(route('panel.onboarding.slug-check')), {
                                 method: 'POST',
                                 headers: {
                                     'Content-Type': 'application/json',
                                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                     'Accept': 'application/json',
                                 },
                                 body: JSON.stringify({ slug: v }),
                             });
                             const data = await res.json();
                             this.slugState = data.ok ? 'ok' : 'error';
                             this.slugMessage = data.message;
                         } catch (e) {
                             this.slugState = 'error';
                             this.slugMessage = 'بررسی شناسه ممکن نشد.';
                         }
                     }, 350);
                 }
             }">

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-ink-900">{{ $menu ? 'اطلاعات کسب‌وکار' : 'بیایید منوی شما را بسازیم' }}</h2>
                <p class="mt-1 text-ink-600">فقط سه قدم کوتاه — کمتر از یک دقیقه.</p>

                {{-- Progress bar --}}
                <div class="mt-6 flex items-center gap-2">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="h-2 flex-1 rounded-full transition-colors" :class="step >= {{ $i }} ? 'bg-brand-500' : 'bg-ink-200'"></div>
                    @endfor
                </div>
                <div class="mt-2 flex justify-between text-xs text-ink-500">
                    <span>نام کسب‌وکار</span>
                    <span>نوع کسب‌وکار</span>
                    <span>شناسه منو</span>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4">
                    <x-alert type="error">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                </div>
            @endif

            <form method="POST" action="{{ route('panel.onboarding.store') }}" class="space-y-8">
                @csrf

                {{-- Step 1: name --}}
                <section x-show="step === 0" x-cloak>
                    <label for="name" class="label">نام کسب‌وکار شما چیست؟</label>
                    <input id="name" name="name" type="text" class="input text-lg"
                           placeholder="مثلاً: کافه الماس"
                           x-model="name" required>
                    <button type="button" class="btn-primary mt-6" @click="name.trim().length >= 2 && (step = 1)">ادامه</button>
                </section>

                {{-- Step 2: business type --}}
                <section x-show="step === 1" x-cloak>
                    <span class="label">نوع کسب‌وکار</span>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($businessTypes as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="business_type" value="{{ $value }}"
                                       class="peer sr-only" x-model="businessType" required>
                                <div class="rounded-2xl border-2 border-ink-200 px-4 py-5 text-center font-medium text-ink-700 transition-colors peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-800 hover:border-brand-300">
                                    {{ $label }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="button" class="btn-ghost" @click="step = 0">بازگشت</button>
                        <button type="button" class="btn-primary" @click="businessType && (step = 2)">ادامه</button>
                    </div>
                </section>

                {{-- Step 3: slug --}}
                <section x-show="step === 2" x-cloak>
                    <label for="slug" class="label">شناسه منو (آدرس اینترنتی)</label>
                    <div class="flex items-center gap-2 rounded-xl border border-ink-200 bg-white px-4" dir="ltr">
                        <span class="text-ink-400 select-none">menoyeman.ir/m/</span>
                        <input id="slug" name="slug" type="text"
                               class="w-full border-0 py-2.5 text-ink-900 focus:outline-none focus:ring-0"
                               placeholder="almas-cafe"
                               x-model="slug"
                               x-on:input="slug = slug.toLowerCase().replace(/[^a-z0-9-]/g, ''); checkSlug()"
                               maxlength="30" required>
                    </div>

                    <p class="mt-2 text-sm"
                       :class="slugState === 'ok' ? 'text-success-600' : slugState === 'error' ? 'text-danger-600' : 'text-ink-500'"
                       x-text="slugState === 'checking' ? 'در حال بررسی…' : slugMessage"></p>

                    <p class="mt-1 text-xs text-ink-500">
                        فقط حروف کوچک انگلیسی، اعداد و خط تیره؛ بین ۳ تا ۳۰ کاراکتر. این شناسه در آدرس منو و کد QR شما استفاده می‌شود.
                    </p>

                    <div class="mt-6 flex gap-3">
                        <button type="button" class="btn-ghost" @click="step = 1">بازگشت</button>
                        <button type="submit" class="btn-primary"
                                :disabled="slugState === 'error' || slugState === 'checking' || slug.length < 3">
                            {{ $menu ? 'ذخیره اطلاعات' : 'ساخت منو' }}
                        </button>
                    </div>
                </section>
            </form>
        </div>
    @endsection
</x-layouts.app>
