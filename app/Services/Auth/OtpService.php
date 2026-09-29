<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\OtpCode;
use App\Services\Sms\SmsManager;
use App\Support\Phone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-time password lifecycle.
 *
 * Security rules (spec §6):
 *  - codes expire after 2 minutes,
 *  - resend is allowed at most once per minute per number,
 *  - send rate limits per number AND per IP,
 *  - max wrong attempts per code, then the code is invalidated,
 *  - only a hash of the code is stored.
 */
class OtpService
{
    public const LENGTH = 6;

    public const EXPIRY_MINUTES = 2;

    public const RESEND_SECONDS = 60;

    public const MAX_SENDS_PER_PHONE_PER_HOUR = 5;

    public const MAX_SENDS_PER_IP_PER_HOUR = 20;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private SmsManager $sms)
    {
    }

    /**
     * Create and send a new OTP. Returns the plain code ONLY when the fake
     * display is enabled (local mode) so it can be shown on screen.
     */
    public function request(string $phone, ?string $ip = null, string $purpose = OtpCode::PURPOSE_LOGIN): OtpRequestResult
    {
        $phone = Phone::normalize($phone);
        if ($phone === null) {
            return OtpRequestResult::invalid();
        }

        if ($wait = $this->resendWaitSeconds($phone)) {
            return OtpRequestResult::tooSoon($wait);
        }

        if ($this->sentToPhoneLastHour($phone) >= self::MAX_SENDS_PER_PHONE_PER_HOUR) {
            return OtpRequestResult::limitExceeded();
        }

        if ($ip !== null && $this->sentFromIpLastHour($ip) >= self::MAX_SENDS_PER_IP_PER_HOUR) {
            return OtpRequestResult::limitExceeded();
        }

        $code = (string) random_int(10 ** (self::LENGTH - 1), 10 ** self::LENGTH - 1);

        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'ip_address' => $ip,
        ]);

        $this->sms->sendOtp($phone, $code, $purpose === OtpCode::PURPOSE_PASSWORD_RESET ? 'password_reset' : 'login');

        $display = config('sms.fake_display') ? $code : null;

        return OtpRequestResult::sent($display, self::RESEND_SECONDS);
    }

    /**
     * Verify a code. Returns true and consumes the code on success.
     */
    public function verify(string $phone, string $code, string $purpose = OtpCode::PURPOSE_LOGIN): bool
    {
        $phone = Phone::normalize($phone);
        if ($phone === null) {
            return false;
        }

        // Defensive: users may type Persian/Arabic digits.
        $code = \App\Support\Persian::toEnglish(trim($code));

        /** @var OtpCode|null $otp */
        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($otp === null) {
            return false;
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->update(['consumed_at' => now()]);

            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);

        // Invalidate older outstanding codes for this phone/purpose.
        OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        return true;
    }

    /**
     * Seconds until another code may be sent (0 = now allowed).
     */
    public function resendWaitSeconds(string $phone): int
    {
        $phone = Phone::normalize($phone);
        if ($phone === null) {
            return 0;
        }

        $last = OtpCode::query()
            ->where('phone', $phone)
            ->latest('id')
            ->first();

        if ($last === null) {
            return 0;
        }

        $elapsed = now()->diffInSeconds($last->created_at, false, \Carbon\CarbonInterface::DIFF_ABSOLUTE);

        return max(0, self::RESEND_SECONDS - (int) $elapsed);
    }

    private function sentToPhoneLastHour(string $phone): int
    {
        return OtpCode::query()
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    private function sentFromIpLastHour(string $ip): int
    {
        return OtpCode::query()
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }
}
