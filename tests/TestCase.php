<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Support\TenantContext;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // TenantContext uses static state (per-process); always start clean.
        TenantContext::reset();

        // CSRF is irrelevant in automated tests and breaks POST assertions
        // with 419. Keep all other web middleware (SecurityHeaders, tenant).
        // In Laravel 13 the CSRF middleware is PreventRequestForgery, not VerifyCsrfToken.
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }
}
