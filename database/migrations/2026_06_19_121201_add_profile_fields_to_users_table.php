<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('other_name', 35)->nullable()->after('name');
            $table->enum('sex', ['male', 'female', 'other'])->default('other')->after('other_name');
            $table->string('phone', 25)->nullable()->after('email');
            $table->string('nrc', 25)->nullable()->unique()->after('phone');
            $table->date('dob')->nullable()->after('nrc');
            $table->string('nationality', 64)->default('Zambian')->after('dob');
            $table->text('address')->nullable()->after('nationality');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('address');
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete()->after('status');
            $table->tinyInteger('is_parent')->default(0)->after('school_id');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite cannot DROP COLUMN on an indexed column
            DB::statement('DROP INDEX IF EXISTS users_nrc_unique');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn([
                'other_name', 'sex', 'phone', 'nrc', 'dob',
                'nationality', 'address', 'status', 'school_id', 'is_parent',
            ]);
        });
    }
};
