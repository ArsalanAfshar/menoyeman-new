<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use App\Support\TenantContext;
use Tests\Concerns\RefreshesDatabase;
use Tests\TestCase;

/**
 * Tenant isolation proof (spec §12): one owner's data must never leak to
 * another owner — at the query level (global scope) and the HTTP level.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshesDatabase;

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function actingAsTenant(User $user, Menu $menu): void
    {
        TenantContext::reset();
        TenantContext::setOwner($user->id);
        TenantContext::setMenu($menu->id);
    }

    public function test_global_scope_hides_other_tenants_rows(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();
        $menuB = Menu::factory()->for($bob, 'owner')->create();

        $catA = Category::factory()->for($menuA)->create(['name' => 'نوشیدنی‌های آلیس']);
        $catB = Category::factory()->for($menuB)->create(['name' => 'نوشیدنی‌های باب']);

        $this->actingAsTenant($alice, $menuA);

        $names = Category::query()->pluck('name')->all();
        $this->assertSame(['نوشیدنی‌های آلیس'], $names);
        $this->assertTrue(Category::query()->whereKey($catA->id)->exists());
        $this->assertFalse(Category::query()->whereKey($catB->id)->exists());
    }

    public function test_finding_foreign_row_returns_null_even_by_id(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();
        $menuB = Menu::factory()->for($bob, 'owner')->create();
        $catB = Category::factory()->for($menuB)->create();

        $this->actingAsTenant($alice, $menuA);

        // Classic IDOR probe: Alice guesses Bob's row id.
        $this->assertNull(Category::query()->find($catB->id));
    }

    public function test_updates_and_deletes_do_not_cross_tenants(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();
        $menuB = Menu::factory()->for($bob, 'owner')->create();
        $catB = Category::factory()->for($menuB)->create(['name' => 'قبل']);

        $this->actingAsTenant($alice, $menuA);

        $affected = Category::query()->whereKey($catB->id)->update(['name' => 'هک']);
        $this->assertSame(0, $affected);

        $deleted = Category::query()->whereKey($catB->id)->delete();
        $this->assertSame(0, $deleted);

        $this->assertSame('قبل', Category::withoutGlobalScope(\App\Scopes\MenuOwnedScope::class)
            ->find($catB->id)->name);
    }

    public function test_created_rows_attach_to_the_active_tenant(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();
        $menuB = Menu::factory()->for($bob, 'owner')->create();

        $this->actingAsTenant($bob, $menuB);
        $created = Category::factory()->create(['name' => 'جدید']);

        $this->assertSame($menuB->id, $created->menu_id);
        $this->assertNull(Category::withoutGlobalScope(\App\Scopes\MenuOwnedScope::class)
            ->where('name', 'جدید')->where('menu_id', $menuA->id)->first());
    }

    public function test_super_admin_bypasses_filtering_explicitly(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();
        $menuB = Menu::factory()->for($bob, 'owner')->create();
        Category::factory()->for($menuA)->create();
        Category::factory()->for($menuB)->create();

        TenantContext::reset();
        TenantContext::bypass(true);

        $this->assertSame(2, Category::query()->count());
    }

    public function test_without_context_queries_are_unfiltered_for_console(): void
    {
        Menu::factory()->create();
        Category::factory()->count(3)->create();

        TenantContext::reset();

        // No tenant context (artisan/queue): no filtering — by design.
        $this->assertSame(3, Category::query()->count());
    }

    public function test_menu_policy_denies_foreign_menus(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();

        $this->assertFalse($bob->can('update', $menuA));
        $this->assertFalse($bob->can('delete', $menuA));
        $this->assertFalse($bob->can('view', $menuA));
        $this->assertTrue($alice->can('update', $menuA));
    }

    public function test_middleware_scoped_request_hides_foreign_rows(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();
        $menuB = Menu::factory()->for($bob, 'owner')->create();
        Category::factory()->for($menuA)->create();

        // Simulate an authenticated request for Bob carrying HIS menu.
        $middleware = new \App\Http\Middleware\SetTenantContext;
        $request = $this->routedRequest("/panel/{$menuB->id}", $bob, $menuB);

        TenantContext::reset();
        $middleware->handle($request, fn ($req) => response('ok'));

        $this->assertSame(0, Category::query()->count(), 'Bob must not see Alice categories in a real request context');
    }

    public function test_set_tenant_middleware_blocks_foreign_menu_route_param(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $menuA = Menu::factory()->for($alice, 'owner')->create();

        // Simulate a panel route with {menu} bound to Alice's menu while Bob is authenticated.
        $middleware = new \App\Http\Middleware\SetTenantContext;
        $request = $this->routedRequest("/panel/{$menuA->id}", $bob, $menuA);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, fn ($req) => response('ok'));
    }

    /** Build a request with a real bound route (mirrors implicit route-model binding). */
    private function routedRequest(string $uri, User $user, ?Menu $menu = null): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create($uri, 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        $route = new \Illuminate\Routing\Route(['GET'], 'panel/{menu}', fn () => response('ok'));
        $route->bind($request);
        if ($menu !== null) {
            $route->setParameter('menu', $menu);
        }
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
