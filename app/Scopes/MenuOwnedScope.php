<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope for menu-scoped models (categories, items, orders, ...).
 *
 * When a tenant context is active, all queries on the model are silently
 * restricted to that tenant's menu. See tests/Feature/Tenancy for the
 * isolation proof.
 */
final class MenuOwnedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::shouldFilter()) {
            $builder->where($model->qualifyColumn('menu_id'), TenantContext::menuId());
        }
    }
}
