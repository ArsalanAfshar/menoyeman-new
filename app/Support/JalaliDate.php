<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Morilog\Jalali\Jalalian;

/**
 * Jalali (Shamsi) date rendering with Persian digits.
 *
 * Dates are STORED as UTC/Gregorian in the database and DISPLAYED as
 * Jalali in Asia/Tehran (config('app.timezone')).
 */
final class JalaliDate
{
    public const TIMEZONE = 'Asia/Tehran';

    /** Jalali month names (Persian). */
    public const MONTHS = [
        1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    /**
     * Jalali date (Y/m/d) with Persian digits, e.g. "۱۴۰۴/۰۷/۰۸".
     */
    public static function date(CarbonInterface|string|null $value = null): string
    {
        return Persian::digits(self::raw($value)->format('Y/m/d'));
    }

    /**
     * Jalali date and time with Persian digits, e.g. "۱۴۰۴/۰۷/۰۸ ۱۴:۳۰".
     */
    public static function dateTime(CarbonInterface|string|null $value = null, bool $withSeconds = false): string
    {
        $format = $withSeconds ? 'Y/m/d H:i:s' : 'Y/m/d H:i';

        return Persian::digits(self::raw($value)->format($format));
    }

    /**
     * Long Persian date, e.g. "۸ مهر ۱۴۰۴".
     */
    public static function long(CarbonInterface|string|null $value = null): string
    {
        $jalali = self::raw($value);

        $month = self::MONTHS[$jalali->getMonth()] ?? '';

        return Persian::digits($jalali->getDay()) . ' ' . $month . ' ' . Persian::digits($jalali->getYear());
    }

    /**
     * Human-readable relative time in Persian, e.g. "۳ دقیقه پیش".
     */
    public static function ago(CarbonInterface|string|null $value = null): string
    {
        return Persian::digits(self::raw($value)->ago());
    }

    /**
     * Underlying Jalalian instance (for advanced formatting).
     */
    public static function raw(CarbonInterface|string|null $value = null): Jalalian
    {
        if ($value === null) {
            return Jalalian::now(new \DateTimeZone(self::TIMEZONE));
        }

        if (is_string($value)) {
            $value = \Illuminate\Support\Carbon::parse($value, self::TIMEZONE);
        }

        return Jalalian::fromCarbon($value->copy()->timezone(self::TIMEZONE));
    }
}
