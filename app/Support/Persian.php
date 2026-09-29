<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Persian (Farsi) text helpers: digits, number formatting and the
 * number-to-Persian-words conversion used by price fields.
 *
 * All user-facing numbers in MenoyeMan are rendered with Persian digits.
 */
final class Persian
{
    public const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';

    public const ARABIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';

    /** Persian thousands separator (U+066C ARABIC THOUSANDS SEPARATOR). */
    public const THOUSANDS_SEPARATOR = '٬';

    /**
     * Convert English digits (and Arabic-Indic digits) to Persian digits.
     */
    public static function digits(string|int|float $value): string
    {
        $value = (string) $value;

        $value = strtr($value, array_combine(range('0', '9'), mb_str_split(self::FA_DIGITS)));
        $value = strtr($value, array_combine(mb_str_split(self::ARABIC_DIGITS), mb_str_split(self::FA_DIGITS)));

        return $value;
    }

    /**
     * Convert Persian/Arabic-Indic digits to English digits (for parsing/storage).
     */
    public static function toEnglish(string $value): string
    {
        $value = strtr($value, array_combine(mb_str_split(self::FA_DIGITS), range('0', '9')));
        $value = strtr($value, array_combine(mb_str_split(self::ARABIC_DIGITS), range('0', '9')));

        return $value;
    }

    /**
     * Group a number with the Persian thousands separator and Persian digits.
     * Example: 200000 -> "۲۰۰٬۰۰۰"
     */
    public static function number(int|float|string $value): string
    {
        $int = (int) floor(abs((float) self::toEnglish((string) $value)));
        $sign = ((float) self::toEnglish((string) $value)) < 0 ? '−' : '';

        $grouped = number_format((float) $int, 0, '.', self::THOUSANDS_SEPARATOR);

        return $sign . self::digits($grouped);
    }

    /**
     * Format a Toman amount with the unit, e.g. "۲۰۰٬۰۰۰ تومان".
     */
    public static function price(int|float|string $value, bool $withUnit = true): string
    {
        return self::number($value) . ($withUnit ? ' تومان' : '');
    }

    /**
     * Convert an integer to Persian words (up to hundreds of billions).
     *
     * Examples:
     *   0        -> "صفر"
     *   200000   -> "دویست هزار"
     *   205500   -> "دویست و پنج هزار و پانصد"
     *   1000000000 -> "یک میلیارد"
     */
    public static function words(int|float|string $value): string
    {
        $number = (int) round(abs((float) self::toEnglish((string) $value)));

        if ($number === 0) {
            return 'صفر';
        }

        $ones = [
            'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه', 'ده',
            'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده',
        ];
        $tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
        $hundreds = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
        $scales = ['', 'هزار', 'میلیون', 'میلیارد'];

        $words = [];
        $groupIndex = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            $number = intdiv($number, 1000);

            if ($chunk > 0) {
                $parts = [];
                $h = intdiv($chunk, 100);
                $rest = $chunk % 100;

                if ($h > 0) {
                    $parts[] = $hundreds[$h];
                }

                if ($rest > 0) {
                    if ($rest < 20) {
                        $parts[] = $ones[$rest - 1];
                    } else {
                        $t = intdiv($rest, 10);
                        $o = $rest % 10;
                        $parts[] = $o > 0 ? $tens[$t] . ' و ' . $ones[$o - 1] : $tens[$t];
                    }
                }

                $chunkWords = implode(' و ', $parts);
                if ($scales[$groupIndex] !== '') {
                    $chunkWords .= ' ' . $scales[$groupIndex];
                }
                array_unshift($words, $chunkWords);
            }

            $groupIndex++;
        }

        return implode(' و ', $words);
    }

    /**
     * Convert an amount to Persian words with the Toman unit.
     * Example: 200000 -> "دویست هزار تومان"
     */
    public static function priceWords(int|float|string $value): string
    {
        return self::words($value) . ' تومان';
    }
}
