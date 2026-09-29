<?php

declare(strict_types=1);

namespace App\Services\Auth;

/**
 * Result of an OTP request. `code` is only populated in local fake mode.
 */
final class OtpRequestResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?string $code = null,
        public readonly int $retryAfterSeconds = 0,
        public readonly ?string $error = null,
    ) {
    }

    public static function sent(?string $code, int $retryAfterSeconds): self
    {
        return new self(true, $code, $retryAfterSeconds);
    }

    public static function tooSoon(int $retryAfterSeconds): self
    {
        return new self(false, null, $retryAfterSeconds, 'too_soon');
    }

    public static function limitExceeded(): self
    {
        return new self(false, null, 0, 'limit_exceeded');
    }

    public static function invalid(): self
    {
        return new self(false, null, 0, 'invalid_phone');
    }
}
