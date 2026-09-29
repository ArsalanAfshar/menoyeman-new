<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Menu;
use App\Scopes\MenuOwnedScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as scoped to a menu (tenant). Adds:
 *  - a `menu()` relation,
 *  - the MenuOwnedScope global scope (automatic tenant filtering),
 *  - a creating hook that stamps the active tenant's menu_id,
 *  - an `ownedByMenu()` local scope for explicit queries.
 */
trait BelongsToMenu
{
    public static function bootBelongsToMenu(): void
    {
        static::addGlobalScope(new MenuOwnedScope);

        // Rows created inside a tenant context always belong to that tenant.
        static::creating(function ($model): void {
            if (TenantContext::menuId() !== null) {
                $model->setAttribute('menu_id', TenantContext::menuId());
            }
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function scopeOwnedByMenu($query, int|Menu $menu)
    {
        $menuId = $menu instanceof Menu ? $menu->id : $menu;

        return $query->withoutGlobalScope(MenuOwnedScope::class)
            ->where($this->qualifyColumn('menu_id'), $menuId);
    }
}
