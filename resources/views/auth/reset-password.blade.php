<x-layouts.guest>
    @section('title', 'تنظیم رمز عبور | منوی من')
    @section('content')
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink-900">انتخاب رمز عبور جدید</h1>
            <p class="mt-2 text-ink-600">رمز عبور جدید خود را وارد کنید. حداقل ۸ کاراکتر.</p>

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

            <form method="POST" action="{{ route('password.reset') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="password" class="label">رمز عبور جدید</label>
                    <input id="password" name="password" type="password" class="input" required autofocus>
                </div>
                <div>
                    <label for="password_confirmation" class="label">تکرار رمز عبور</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" required>
                </div>

                <button type="submit" class="btn-primary w-full">ثبت رمز عبور</button>
            </form>
        </div>
    @endsection
</x-layouts.guest>
