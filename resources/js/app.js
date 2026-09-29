/*
|--------------------------------------------------------------------------
| MenoyeMan frontend entry
|--------------------------------------------------------------------------
| Alpine.js (UI behavior) + shared Persian helpers used by Blade views.
| No external CDNs: everything is bundled locally (see vite.config.js).
*/
import Alpine from 'alpinejs';

/* ------------------------------------------------------------------ *
 * Persian number helpers (mirrors App\Support\Persian on the server)  *
 * ------------------------------------------------------------------ */
const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const EN_DIGITS = '0123456789';

/** Convert English digits in a string to Persian digits. */
export function toPersianDigits(value) {
    return String(value).replace(/[0-9]/g, (d) => FA_DIGITS[+d]);
}

/** Convert Persian/Arabic digits in a string to English digits. */
export function toEnglishDigits(value) {
    return String(value)
        .replace(/[۰-۹]/g, (d) => String(FA_DIGITS.indexOf(d)))
        .replace(/[٠-٩]/g, (d) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)));
}

/**
 * Format a number with Persian digits and the Persian thousands separator
 * (٬ U+066C). Example: 200000 -> "۲۰۰٬۰۰۰"
 */
export function formatNumberFa(value) {
    const n = Math.trunc(Math.abs(Number(toEnglishDigits(value)) || 0));
    const grouped = n.toLocaleString('en-US').replace(/,/g, '٬');
    const sign = Number(toEnglishDigits(value)) < 0 ? '−' : '';
    return sign + toPersianDigits(grouped);
}

/** Format Toman amount with unit: 200000 -> "۲۰۰٬۰۰۰ تومان" */
export function formatPriceFa(value) {
    return formatNumberFa(value) + ' تومان';
}

// Expose globally for inline Blade scripts.
window.MenoyeMan = {
    toPersianDigits,
    toEnglishDigits,
    formatNumberFa,
    formatPriceFa,
};

/* ------------------------------------------------------------------ *
 * Alpine                                                              *
 * ------------------------------------------------------------------ */
window.Alpine = Alpine;
Alpine.start();
