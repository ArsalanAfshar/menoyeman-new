<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

/**
 * Same behavior as Laravel's RefreshDatabase trait, but migrations run through
 * Artisan::call() instead of the $this->artisan() test helper. The helper's
 * output-capture machinery crashes the php-wasm runtime used in this workspace
 * ("RuntimeError: unreachable"); on real PHP both paths are equivalent.
 */
trait RefreshesDatabase
{
    use RefreshDatabase {
        migrateDatabases as private frameworkMigrateDatabases;
    }

    protected function migrateDatabases()
    {
        Artisan::call('migrate:fresh', $this->migrateFreshUsing());

        $this->app[Kernel::class]->setArtisan(null);

        $this->updateLocalCacheOfInMemoryDatabases();
    }
}
