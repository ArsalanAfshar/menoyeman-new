<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'name' => fake()->randomElement(['نوشیدنی‌ها', 'غذای اصلی', 'پیش‌غذا', 'دسر', 'سالاد', 'صبحانه']),
            'position' => fake()->numberBetween(0, 20),
            'is_visible' => true,
        ];
    }
}
