<?php

declare(strict_types=1);

namespace App\Services\Menus;

use App\Models\Menu;

/**
 * Menu ID (slug) rules — spec §6.
 *
 *  - lowercase English letters, digits and hyphens only,
 *  - 3 to 30 characters,
 *  - must not start or end with a hyphen,
 *  - globally unique,
 *  - not a reserved word.
 */
final class SlugRules
{
    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 30;

    public const PATTERN = '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])$/';

    /**
     * Reserved IDs that can never be registered as a menu ID.
     * (Editable list is managed by the super admin in Phase 6; this is the
     * baseline shipped in code.)
     */
    public const RESERVED = [
        'admin', 'api', 'login', 'logout', 'register', 'signup', 'signin',
        'app', 'panel', 'dashboard', 'www', 'menu', 'menoyeman', 'menoo',
        'support', 'static', 'assets', 'build', 'storage', 'vendor', 'public',
        'mail', 'ftp', 'cdn', 'images', 'img', 'css', 'js', 'fonts',
        'blog', 'help', 'docs', 'doc', 'terms', 'privacy', 'contact', 'about',
        'pricing', 'plans', 'm', 'p', 'q', 's', 'new', 'official',
        'orders', 'order', 'settings', 'billing', 'payment', 'payments',
        'tickets', 'ticket', 'staff', 'qr', 'robots', 'sitemap', 'favicon',
        'status', 'health', 'up', 'down', 'test', 'testing', 'demo', 'null',
        'undefined', 'phpmyadmin', 'cpanel', 'webmail', 'email', 'smtp',
        'notifications', 'profile', 'account', 'user', 'users', 'auth',
        'password', 'otp', 'session', 'sessions', 'oauth', 'callback',
    ];

    /**
     * Validate and normalize a candidate slug.
     *
     * @return array{ok: bool, slug: ?string, error: ?string}
     */
    public static function validate(string $input): array
    {
        $slug = mb_strtolower(trim($input));

        if ($slug === '') {
            return ['ok' => false, 'slug' => null, 'error' => 'empty'];
        }

        if (mb_strlen($slug) < self::MIN_LENGTH) {
            return ['ok' => false, 'slug' => null, 'error' => 'too_short'];
        }

        if (mb_strlen($slug) > self::MAX_LENGTH) {
            return ['ok' => false, 'slug' => null, 'error' => 'too_long'];
        }

        if (! preg_match(self::PATTERN, $slug)) {
            return ['ok' => false, 'slug' => null, 'error' => 'invalid_chars'];
        }

        if (in_array($slug, self::RESERVED, true)) {
            return ['ok' => false, 'slug' => null, 'error' => 'reserved'];
        }

        return ['ok' => true, 'slug' => $slug, 'error' => null];
    }

    /**
     * Full availability check (format + uniqueness).
     */
    public static function check(string $input, ?int $ignoreMenuId = null): array
    {
        $result = self::validate($input);
        if (! $result['ok']) {
            return $result;
        }

        $query = Menu::withTrashed()->where('slug', $result['slug']);
        if ($ignoreMenuId !== null) {
            $query->where('id', '!=', $ignoreMenuId);
        }

        if ($query->exists()) {
            return ['ok' => false, 'slug' => $result['slug'], 'error' => 'taken'];
        }

        return $result;
    }

    /**
     * Persian error message for each failure reason.
     */
    public static function errorMessage(?string $error): string
    {
        return match ($error) {
            'empty' => 'شناسه منو را وارد کنید.',
            'too_short' => 'شناسه منو باید حداقل ۳ کاراکتر باشد.',
            'too_long' => 'شناسه منو باید حداکثر ۳۰ کاراکتر باشد.',
            'invalid_chars' => 'شناسه منو فقط می‌تواند شامل حروف کوچک انگلیسی، اعداد و خط تیره باشد و نباید با خط تیره شروع یا تمام شود.',
            'reserved' => 'این شناسه رزرو شده است و قابل استفاده نیست. شناسه دیگری انتخاب کنید.',
            'taken' => 'این شناسه قبلاً ثبت شده است. شناسه دیگری انتخاب کنید.',
            default => 'شناسه منو معتبر نیست.',
        };
    }
}
