<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('subdomain')->unique()->nullable()->after('code');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('headteacher_id');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropUnique(['subdomain']);
            $table->dropColumn(['subdomain', 'owner_id']);
        });
    }
};
