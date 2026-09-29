<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registers the authenticated tenant (owner + active menu/branch) in the
 * TenantContext so MenuOwnedScope can filter all scoped queries.
 *
 * Super admins bypass filtering explicitly (support/impersonation flows).
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        TenantContext::setOwner($user->id);

        if ($user->isSuperAdmin()) {
            TenantContext::bypass(true);

            return $next($request);
        }

        // Determine the active menu: explicit route parameter, then the
        // session selection, then the owner's first menu.
        $menu = $request->route('menu');
        if ($menu !== null && $menu instanceof \App\Models\Menu) {
            abort_unless($menu->user_id === $user->id, 403);
            TenantContext::setMenu($menu->id);
        } else {
            $menuId = $request->session()->get('active_menu_id');
            TenantContext::setMenu($menuId ? (int) $menuId : $user->menus()->value('id'));
        }

        return $next($request);
    }
}
