<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Request-scoped tenant context.
 *
 * Owner-panel routes run `SetTenantContext` middleware which registers the
 * authenticated owner (and the active menu/branch) here. `MenuOwnedScope`
 * then restricts every menu-scoped query to that menu automatically, so
 * data can never leak between owners — even if a developer forgets a
 * `where('menu_id', ...)` clause.
 *
 * Super-admin flows (or artisan/queue jobs that legitimately span tenants)
 * call `bypass()` explicitly; nothing bypasses implicitly.
 */
final class TenantContext
{
    private static ?int $ownerId = null;

    private static ?int $menuId = null;

    private static bool $bypass = false;

    public static function setOwner(?int $ownerId): void
    {
        self::$ownerId = $ownerId;
    }

    public static function setMenu(?int $menuId): void
    {
        self::$menuId = $menuId;
    }

    public static function ownerId(): ?int
    {
        return self::$ownerId;
    }

    public static function menuId(): ?int
    {
        return self::$menuId;
    }

    /**
     * Disable/enables row filtering. Must only be called by trusted code:
     * super-admin controllers, maintenance commands and test setups.
     */
    public static function bypass(bool $bypass = true): void
    {
        self::$bypass = $bypass;
    }

    public static function isBypassing(): bool
    {
        return self::$bypass;
    }

    /**
     * Should menu-scoped queries be filtered to the current menu?
     */
    public static function shouldFilter(): bool
    {
        return ! self::$bypass && self::$menuId !== null;
    }

    public static function reset(): void
    {
        self::$ownerId = null;
        self::$menuId = null;
        self::$bypass = false;
    }
}
