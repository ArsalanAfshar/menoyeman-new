<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Phone + OTP login (primary), password login (secondary), logout.
 */
class LoginController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    /** Login page (OTP tab). */
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('panel.dashboard');
        }

        return view('auth.login');
    }

    /** Send a login OTP. */
    public function sendOtp(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            // Honeypot — silently pretend success to bots.
            return redirect()->route('login.verify')->with('phone', $request->string('phone'));
        }

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ], [], ['phone' => 'شماره موبایل']);

        $phone = Phone::normalize($data['phone']);
        if ($phone === null) {
            return back()
                ->withErrors(['phone' => 'شماره موبایل معتبر نیست. شماره‌ای مانند ۰۹۱۲۳۴۵۶۷۸۹ وارد کنید.'])
                ->withInput();
        }

        $ipKey = 'otp-send:ip:' . ($request->ip() ?? 'unknown');
        $phoneKey = 'otp-send:phone:' . $phone;

        if (RateLimiter::tooManyAttempts($ipKey, 10) || RateLimiter::tooManyAttempts($phoneKey, 5)) {
            $seconds = max(
                RateLimiter::availableIn($ipKey),
                RateLimiter::availableIn($phoneKey),
            );

            return back()
                ->withErrors(['phone' => 'تعداد درخواست‌ها زیاد است. لطفاً ' . $seconds . ' ثانیه دیگر دوباره تلاش کنید.'])
                ->withInput();
        }

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($phoneKey, 3600);

        $result = $this->otp->request($phone, $request->ip());

        if (! $result->ok) {
            $message = match ($result->error) {
                'too_soon' => 'کد به‌تازگی ارسال شده است. لطفاً ' . $result->retryAfterSeconds . ' ثانیه صبر کنید.',
                'limit_exceeded' => 'سقف ارسال پیامک رسیده است. بعداً تلاش کنید.',
                default => 'ارسال کد ممکن نشد. شماره را بررسی کنید.',
            };

            return back()->withErrors(['phone' => $message])->withInput();
        }

        $session = ['phone' => $phone, 'otp_purpose' => 'login'];
        if ($result->code !== null) {
            $session['otp_fake_code'] = $result->code; // local fake mode only
        }

        // Persistent (not flash): the flow spans GET verify + POST verify.
        $request->session()->put($session);

        return redirect()->route('login.verify');
    }

    /** OTP verification page. */
    public function showVerify(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get('phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        return view('auth.verify', [
            'phone' => $phone,
            'fakeCode' => $request->session()->get('otp_fake_code'),
            'resendAfter' => $this->otp->resendWaitSeconds($phone),
            'purpose' => $request->session()->get('otp_purpose', 'login'),
        ]);
    }

    /** Verify the OTP and log the user in. */
    public function verify(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [], ['code' => 'کد تایید']);

        $purpose = $request->session()->get('otp_purpose', 'login');

        if (! $this->otp->verify($phone, $data['code'], $purpose === 'password_reset' ? OtpCode::PURPOSE_PASSWORD_RESET : OtpCode::PURPOSE_LOGIN)) {
            return back()
                ->withErrors(['code' => 'کد وارد شده نادرست یا منقضی شده است.'])
                ->withInput();
        }

        if ($purpose === 'password_reset') {
            return redirect()->route('password.reset.form');
        }

        $user = $this->findOrCreateUser($phone);

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['phone' => 'حساب کاربری شما غیرفعال شده است. با پشتیبانی تماس بگیرید.']);
        }

        Auth::login($user, remember: true);
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $request->session()->forget(['phone', 'otp_fake_code', 'otp_purpose']);

        return redirect()->intended(route('panel.dashboard'));
    }

    /** Resend the OTP code. */
    public function resend(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        $result = $this->otp->request($phone, $request->ip(), $request->session()->get('otp_purpose', 'login'));

        if (! $result->ok) {
            return back()->withErrors([
                'code' => $result->error === 'too_soon'
                    ? 'هنوز ' . $result->retryAfterSeconds . ' ثانیه تا ارسال مجدد مانده است.'
                    : 'ارسال مجدد کد ممکن نشد. بعداً تلاش کنید.',
            ]);
        }

        $session = ['otp_resent' => true];
        if ($result->code !== null) {
            $request->session()->put('otp_fake_code', $result->code);
        }

        return back()->with($session);
    }

    /** Password login page. */
    public function showPasswordLogin(): View
    {
        return view('auth.password-login');
    }

    /** Log in with phone + password. */
    public function passwordLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'phone.required' => 'شماره موبایل را وارد کنید.',
            'password.required' => 'رمز عبور را وارد کنید.',
        ]);

        $phone = Phone::normalize($data['phone']);
        $user = $phone ? User::query()->where('phone', $phone)->first() : null;

        if ($user === null
            || $user->password === null
            || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['password' => 'شماره موبایل یا رمز عبور نادرست است.'])
                ->withInput($request->only('phone'));
        }

        if (! $user->is_active) {
            return back()
                ->withErrors(['password' => 'حساب کاربری شما غیرفعال شده است.'])
                ->withInput($request->only('phone'));
        }

        Auth::login($user, remember: true);
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('panel.dashboard'));
    }

    /** Start the "forgot password" OTP flow. */
    public function forgotPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ], [], ['phone' => 'شماره موبایل']);

        $phone = Phone::normalize($data['phone']);
        if ($phone === null) {
            return back()->withErrors(['phone' => 'شماره موبایل معتبر نیست.'])->withInput();
        }

        $result = $this->otp->request($phone, $request->ip(), 'password_reset');

        $session = ['phone' => $phone, 'otp_purpose' => 'password_reset'];
        if ($result->code !== null) {
            $session['otp_fake_code'] = $result->code;
        }

        $request->session()->put($session);

        return redirect()->route('login.verify');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function findOrCreateUser(string $phone): User
    {
        return User::query()->firstOrCreate(
            ['phone' => $phone],
            ['role' => User::ROLE_OWNER, 'name' => null, 'is_active' => true],
        );
    }
}
