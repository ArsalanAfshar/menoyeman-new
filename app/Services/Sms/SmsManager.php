<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\Persian;
use Illuminate\Support\Facades\Log;

/**
 * SMS sending with pluggable drivers.
 *
 * Local mode (`log` driver) never talks to a network: messages go to the
 * Laravel log and the OTP can be displayed on screen (config sms.fake_display).
 * Production uses sms.ir (see SmsIrDriver, Phase 5).
 */
class SmsManager
{
    /**
     * Send a text message to a phone number (canonical 09xxxxxxxxx form).
     *
     * @param  array<string, string>  $vars  template variables (sms.ir verify API)
     */
    public function send(string $phone, string $message, array $vars = []): bool
    {
        $driver = config('sms.driver', 'log');

        return match ($driver) {
            'smsir' => app(SmsIrDriver::class)->send($phone, $message, $vars),
            default => $this->sendFake($phone, $message),
        };
    }

    /**
     * Send a login OTP through the configured driver.
     */
    public function sendOtp(string $phone, string $code, string $template = 'login'): bool
    {
        $message = $this->otpMessage($template, $code);

        return $this->send($phone, $message, ['code' => Persian::digits($code)]);
    }

    protected function sendFake(string $phone, string $message): bool
    {
        Log::info('[FAKE SMS]', ['phone' => $phone, 'message' => $message]);

        return true;
    }

    protected function otpMessage(string $template, string $code): string
    {
        $codeFa = Persian::digits($code);

        return match ($template) {
            'password_reset' => "منوی من: کد بازیابی رمز عبور شما: {$codeFa}",
            default => "منوی من: کد ورود یک‌بار مصرف شما: {$codeFa}",
        };
    }
}
