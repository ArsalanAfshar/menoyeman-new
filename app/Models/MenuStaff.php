<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Staff assignment: a limited user (waiter/cashier) attached to one menu.
 */
class MenuStaff extends Model
{
    protected $fillable = [
        'menu_id',
        'user_id',
        'can_toggle_soldout',
    ];

    protected function casts(): array
    {
        return [
            'can_toggle_soldout' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
