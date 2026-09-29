<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A menu = one business branch of an owner. The `slug` is the public
 * menu ID used in URLs and QR codes (`/m/{slug}` or `menu.domain/{slug}`).
 */
class Menu extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SUSPENDED = 'suspended';

    public const BUSINESS_TYPES = [
        'restaurant' => 'رستوران',
        'fast_food' => 'فست‌فود',
        'cafe' => 'کافه',
        'icecream_juice' => 'بستنی و آبمیوه',
        'other' => 'سایر',
    ];

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'business_type',
        'status',
        'is_ordering_enabled',
        'settings',
        'trial_ends_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_ordering_enabled' => 'boolean',
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /* ----------------------------- Relations ----------------------------- */

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'menu_staff')
            ->withPivot('can_toggle_soldout')
            ->withTimestamps();
    }

    /* ------------------------------ Helpers ------------------------------ */

    /**
     * Find a menu by its public slug. Public menu routes are intentionally
     * NOT tenant-scoped (guests have no tenant), so this is the one
     * sanctioned lookup outside the owner context.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }

    public function businessTypeLabel(): string
    {
        return self::BUSINESS_TYPES[$this->business_type] ?? $this->business_type;
    }

    /** Is the public menu currently usable by guests? */
    public function isPubliclyAvailable(): bool
    {
        return $this->status === self::STATUS_TRIAL
            || $this->status === self::STATUS_ACTIVE
            || ($this->status === self::STATUS_EXPIRED && $this->expires_at !== null && $this->expires_at->gt(now()));
    }
}
