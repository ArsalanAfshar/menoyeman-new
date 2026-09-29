<?php

declare(strict_types=1);

namespace Tests\Feature\Menus;

use App\Models\Menu;
use App\Services\Menus\SlugRules;
use Tests\Concerns\RefreshesDatabase;
use Tests\TestCase;

class SlugRulesTest extends TestCase
{
    use RefreshesDatabase;

    public function test_valid_slugs_are_accepted(): void
    {
        foreach (['almas-cafe', 'kababi1', 'a1b', 'tehran-food', 'x-y-z', 'abc'] as $slug) {
            $result = SlugRules::validate($slug);
            $this->assertTrue($result['ok'], "Expected {$slug} to be valid");
            $this->assertSame($slug, $result['slug']);
        }
    }

    public function test_uppercase_input_is_lowercased(): void
    {
        $result = SlugRules::validate('Arsalan');
        $this->assertTrue($result['ok']);
        $this->assertSame('arsalan', $result['slug']);
    }

    public function test_invalid_characters_are_rejected(): void
    {
        foreach (['کافه-الماس', 'cafe almas', 'cafe_almas', 'cafe.com', 'cafe@x', 'کافه'] as $slug) {
            $result = SlugRules::validate($slug);
            $this->assertFalse($result['ok'], "Expected {$slug} to be invalid");
        }
    }

    public function test_length_limits(): void
    {
        $this->assertSame('too_short', SlugRules::validate('ab')['error']);
        $this->assertTrue(SlugRules::validate('abc')['ok']);
        $this->assertTrue(SlugRules::validate(str_repeat('a', 30))['ok']);
        $this->assertSame('too_long', SlugRules::validate(str_repeat('a', 31))['error']);
    }

    public function test_hyphen_placement_rules(): void
    {
        $this->assertSame('invalid_chars', SlugRules::validate('-abc')['error']);
        $this->assertSame('invalid_chars', SlugRules::validate('abc-')['error']);
    }

    public function test_reserved_words_are_rejected(): void
    {
        foreach (['admin', 'api', 'login', 'panel', 'www', 'menu', 'support', 'assets', 'ADMIN'] as $slug) {
            $result = SlugRules::validate($slug);
            $this->assertFalse($result['ok'], "Expected {$slug} to be reserved");
            $this->assertSame('reserved', $result['error']);
        }
    }

    public function test_uniqueness_check_covers_soft_deleted_menus(): void
    {
        Menu::factory()->create(['slug' => 'almas-cafe']);

        $result = SlugRules::check('almas-cafe');
        $this->assertFalse($result['ok']);
        $this->assertSame('taken', $result['error']);

        // The same slug is fine when editing that very menu.
        $menu = Menu::query()->where('slug', 'almas-cafe')->first();
        $this->assertTrue(SlugRules::check('almas-cafe', $menu->id)['ok']);
    }

    public function test_error_messages_are_persian(): void
    {
        foreach (['empty', 'too_short', 'too_long', 'invalid_chars', 'reserved', 'taken'] as $error) {
            $message = SlugRules::errorMessage($error);
            $this->assertNotSame('', $message);
            $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $message);
        }
    }
}
