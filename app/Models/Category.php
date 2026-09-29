<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToMenu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A menu category (e.g. "نوشیدنی‌ها"). Tenant-scoped via BelongsToMenu.
 */
class Category extends Model
{
    use BelongsToMenu, HasFactory, SoftDeletes;

    protected $fillable = [
        'menu_id',
        'name',
        'position',
        'is_visible',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'position' => 'integer',
        ];
    }
}
