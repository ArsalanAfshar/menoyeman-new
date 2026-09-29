<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Iranian mobile number normalization and validation.
 *
 * Accepts all common formats (Persian/Arabic digits allowed) and
 * normalizes to the canonical `09xxxxxxxxx` form:
 *   0912... / 98912... / +98912... / 0098912... / ۰۹۱۲...
 */
final class Phone
{
    /** Canonical Iranian mobile regex: 09 + 9 digits. */
    public const MOBILE_REGEX = '/^09[0-9]{9}$/';

    /**
     * Normalize any accepted format to `09xxxxxxxxx`.
     * Returns null when the value is not a valid Iranian mobile number.
     */
    public static function normalize(string $value): ?string
    {
        $value = trim($value);
        $value = Persian::toEnglish($value);

        // Strip visual separators users often type.
        $value = preg_replace('/[\s\-\(\)\.]/u', '', $value) ?? '';

        if (str_starts_with($value, '0098')) {
            $value = '0' . substr($value, 4);
        } elseif (str_starts_with($value, '+98')) {
            $value = '0' . substr($value, 3);
        } elseif (str_starts_with($value, '98')) {
            $value = '0' . substr($value, 2);
        } elseif (str_starts_with($value, '9')) {
            $value = '0' . $value;
        }

        return self::isValid($value) ? $value : null;
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match(self::MOBILE_REGEX, $value);
    }

    /**
     * Mask for display in admin/owner contexts: ۰۹۱۲ *** ۴۵۶۷
     */
    public static function mask(string $value): string
    {
        $normalized = self::normalize($value) ?? Persian::toEnglish($value);
        if (strlen($normalized) < 7) {
            return Persian::digits($normalized);
        }

        return Persian::digits(substr($normalized, 0, 4) . ' *** ' . substr($normalized, -4));
    }
}
