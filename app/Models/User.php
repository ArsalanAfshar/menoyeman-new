<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A MenoyeMan user: `super_admin` (site owner), `owner` (customer business)
 * or `staff` (limited account that can manage orders only).
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_OWNER = 'owner';

    public const ROLE_STAFF = 'staff';

    protected $fillable = [
        'phone',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Defaults hydrated on new instances (DB defaults are not). */
    protected $attributes = [
        'role' => 'owner',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /* ----------------------------- Relations ----------------------------- */

    /** Menus (branches) owned by this user. */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /** Menus this user works on as staff. */
    public function staffMenus(): HasMany
    {
        return $this->hasMany(MenuStaff::class);
    }

    /* ------------------------------- Roles ------------------------------- */

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }
}
