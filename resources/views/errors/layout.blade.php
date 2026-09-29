{{-- Shared error page layout (Persian, branded). --}}
<x-layouts.guest>
    @section('title', $title)
    @section('content')
        <div class="card p-10 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-brand-100">
                <span class="text-4xl" aria-hidden="true">{{ $emoji }}</span>
            </div>
            <p class="mt-6 text-6xl font-black text-brand-600 fa-digits">{{ \App\Support\Persian::digits($code) }}</p>
            <h1 class="mt-3 text-2xl font-bold text-ink-900">{{ $title }}</h1>
            <p class="mt-3 text-ink-600">{{ $message }}</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('home') }}" class="btn-primary">بازگشت به صفحه اصلی</a>
                <a href="javascript:history.back()" class="btn-secondary">بازگشت</a>
            </div>
        </div>
    @endsection
</x-layouts.guest>
