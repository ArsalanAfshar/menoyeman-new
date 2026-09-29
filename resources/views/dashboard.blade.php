<x-layouts.app :user="$user" :menu="$menu">
    @section('title', 'داشبورد')

    @section('content')
        {{-- Greeting --}}
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-ink-900">{{ $greeting }}{{ $user->name ? '، ' . $user->name : '' }} 👋</h2>
            <p class="mt-1 text-ink-600">
                امروز
                <span class="font-medium">{{ $today }}</span>
                است.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Getting started checklist --}}
            <div class="card p-6 lg:col-span-2">
                <h3 class="text-lg font-bold text-ink-900">شروع کار</h3>
                <p class="mt-1 text-sm text-ink-600">با انجام این مراحل، منوی شما آماده می‌شود.</p>

                <ol class="mt-5 space-y-4">
                    @php $steps = [
                        ['label' => 'ساخت منوی کسب‌وکار', 'done' => $menu !== null, 'href' => route('panel.onboarding')],
                        ['label' => 'افزودن دسته‌بندی', 'done' => false, 'href' => '#'],
                        ['label' => 'افزودن اولین آیتم', 'done' => false, 'href' => '#'],
                        ['label' => 'دانلود کد QR منو', 'done' => false, 'href' => '#'],
                    ]; @endphp
                    @foreach ($steps as $i => $step)
                        <li class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold
                                {{ $step['done'] ? 'bg-success-600 text-white' : 'bg-brand-100 text-brand-800' }}">
                                @if ($step['done'])
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ \App\Support\Persian::digits($i + 1) }}
                                @endif
                            </span>
                            <a href="{{ $step['href'] }}" class="font-medium text-ink-800 hover:text-brand-700 {{ $step['done'] ? 'line-through decoration-success-600/50' : '' }}">
                                {{ $step['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ol>

                @if (! $menu)
                    <div class="mt-6">
                        <a href="{{ route('panel.onboarding') }}" class="btn-primary">ساخت منوی من</a>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                {{-- Subscription --}}
                <div class="card p-6">
                    <h3 class="text-sm font-medium text-ink-600">وضعیت اشتراک</h3>
                    <p class="mt-2 text-3xl font-bold text-brand-700">
                        @if ($menu && $menu->trial_ends_at)
                            {{ \App\Support\Persian::digits(max(0, now()->diffInDays($menu->trial_ends_at, false))) }}
                            <span class="text-base font-medium text-ink-600">روز باقی‌مانده</span>
                        @else
                            رایگان
                        @endif
                    </p>
                    <p class="mt-2 text-sm text-ink-600">
                        @if ($menu && $menu->trial_ends_at)
                            پایان دوره آزمایشی:
                            <span class="fa-digits">{{ \App\Support\JalaliDate::date($menu->trial_ends_at) }}</span>
                        @else
                            دوره آزمایشی ۱۰ روزه پس از ساخت منو آغاز می‌شود.
                        @endif
                    </p>
                    <a href="#" class="btn-secondary compact mt-4 w-full opacity-50 pointer-events-none">مشاهده پلن‌ها (به‌زودی)</a>
                </div>

                {{-- QR shortcut --}}
                <div class="card p-6">
                    <h3 class="text-sm font-medium text-ink-600">دسترسی سریع</h3>
                    <div class="mt-3 space-y-2">
                        <a href="#" class="btn-ghost compact flex w-full justify-between opacity-50 pointer-events-none">
                            دانلود کد QR
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v12"/><path d="M7 12l5 5 5-5"/><path d="M5 20h14"/></svg>
                        </a>
                        <a href="#" class="btn-ghost compact flex w-full justify-between opacity-50 pointer-events-none">
                            سفارش‌های جدید
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16l-1.5 12H5.5L4 6z"/><path d="M9 10v4M15 10v4"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endsection
</x-layouts.app>
