<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Set / reset the account password (always after a successful OTP check).
 */
class PasswordController extends Controller
{
    /** Show the "choose a new password" form (after OTP verification). */
    public function showResetForm(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('otp_purpose') !== 'password_reset') {
            return redirect()->route('login');
        }

        return view('auth.reset-password');
    }

    /** Save the new password and log the user in. */
    public function reset(Request $request): RedirectResponse
    {
        if ($request->session()->get('otp_purpose') !== 'password_reset') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ], [
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ], ['password' => 'رمز عبور']);

        $phone = $request->session()->get('phone');
        $user = \App\Models\User::query()->where('phone', $phone)->firstOrFail();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        Auth::login($user, remember: true);
        $request->session()->forget(['phone', 'otp_purpose', 'otp_fake_code']);

        return redirect()->route('panel.dashboard')->with('status', 'رمز عبور شما با موفقیت تنظیم شد.');
    }

    /** Change password from inside the panel (requires current password if set). */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ], [
            'password.required' => 'رمز عبور جدید را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'current_password.current_password' => 'رمز عبور فعلی نادرست است.',
        ], ['password' => 'رمز عبور']);

        $user = $request->user();

        if ($user->password !== null && ! Hash::check($data['current_password'] ?? '', $user->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی نادرست است.']);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('status', 'رمز عبور شما با موفقیت تغییر کرد.');
    }
}
