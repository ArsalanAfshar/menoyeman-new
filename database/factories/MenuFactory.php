<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slug' => Str::lower(fake()->unique()->regexify('[a-z]{3,10}-[a-z]{3,10}')),
            'name' => fake()->company(),
            'business_type' => fake()->randomElement(array_keys(Menu::BUSINESS_TYPES)),
            'status' => Menu::STATUS_TRIAL,
            'is_ordering_enabled' => true,
            'trial_ends_at' => now()->addDays(10),
        ];
    }
}
