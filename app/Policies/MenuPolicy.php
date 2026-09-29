<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

/**
 * Menus are private to their owner (and super admins for support).
 * Staff never manage menu structure — orders only (Phase 4).
 */
class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isSuperAdmin();
    }

    public function view(User $user, Menu $menu): bool
    {
        return $this->owns($user, $menu);
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isSuperAdmin();
    }

    public function update(User $user, Menu $menu): bool
    {
        return $this->owns($user, $menu);
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $this->owns($user, $menu);
    }

    private function owns(User $user, Menu $menu): bool
    {
        return $user->isSuperAdmin() || $menu->user_id === $user->id;
    }
}
