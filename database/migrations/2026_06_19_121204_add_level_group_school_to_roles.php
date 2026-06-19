<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->integer('level')->default(1)->after('guard_name');
            $table->integer('group')->default(1)->after('level');
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete()->after('group');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn(['level', 'group', 'school_id']);
        });
    }
};
