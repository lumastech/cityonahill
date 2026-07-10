<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// One-way migration: the product moved from multi-tenant SaaS (subdomain
// resolution, onboarding applications, subscriptions) to a single-tenant
// install with multiple school branches. down() intentionally restores
// nothing — the removed features have no code paths left.
return new class extends Migration
{
    public function up(): void
    {
        // Children before parents to satisfy FK constraints.
        Schema::dropIfExists('school_application_logs');
        Schema::dropIfExists('school_applications');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');

        if (Schema::hasColumn('schools', 'subdomain')) {
            // The index must go first on every driver — SQLite refuses to
            // drop a column that is still referenced by an index.
            if (Schema::hasIndex('schools', 'schools_subdomain_unique')) {
                Schema::table('schools', function ($table) {
                    $table->dropUnique(['subdomain']);
                });
            }

            Schema::table('schools', function ($table) {
                $table->dropColumn('subdomain');
            });
        }

        // Menus are DB-driven; remove entries pointing at deleted routes.
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route', 'like', 'admin.applications%')->delete();
            DB::table('menus')->where('route', 'like', 'onboarding.%')->delete();
        }
    }

    public function down(): void
    {
        // Intentionally empty — see note above.
    }
};
