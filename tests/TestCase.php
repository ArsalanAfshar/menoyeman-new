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
    }
}
