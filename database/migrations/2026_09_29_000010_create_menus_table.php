<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "menu" is one business branch of an owner (multi-branch support).
     * All menu-scoped resources (categories, items, orders, ...) belong to
     * exactly one menu and are isolated per owner (see App\Models\Concerns\BelongsToMenu).
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 30)->unique(); // public menu ID, e.g. "almas-cafe"
            $table->string('name');
            $table->string('business_type', 30)->default('restaurant');
            $table->string('status', 20)->default('trial'); // trial | active | expired | suspended
            $table->boolean('is_ordering_enabled')->default(true);
            $table->json('settings')->nullable(); // appearance & business info (Phase 2+)
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('business_type');
        });

        Schema::create('menu_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_toggle_soldout')->default(true);
            $table->timestamps();

            $table->unique(['menu_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_staff');
        Schema::dropIfExists('menus');
    }
};
