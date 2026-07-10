<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (DB::getDriverName() === 'sqlite') {
            // SQLite cannot DROP COLUMN on an indexed column (subdomain) or one
            // in a foreign key definition (owner_id) — rebuild the table.
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement("CREATE TABLE schools_tmp (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR NOT NULL,
                code VARCHAR NOT NULL,
                type VARCHAR NOT NULL,
                level VARCHAR NOT NULL,
                province VARCHAR NOT NULL,
                district VARCHAR NOT NULL,
                address TEXT NOT NULL,
                phone VARCHAR NOT NULL,
                email VARCHAR,
                website VARCHAR,
                moe_registration_no VARCHAR,
                headteacher_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                established_year INTEGER,
                status VARCHAR NOT NULL DEFAULT 'active',
                created_at DATETIME,
                updated_at DATETIME
            )");

            DB::statement('INSERT INTO schools_tmp
                SELECT id, name, code, type, level, province, district, address, phone,
                       email, website, moe_registration_no, headteacher_id,
                       established_year, status, created_at, updated_at
                FROM schools');

            DB::statement('DROP TABLE schools');
            DB::statement('ALTER TABLE schools_tmp RENAME TO schools');

            DB::statement('CREATE UNIQUE INDEX schools_code_unique ON schools(code)');
            DB::statement('CREATE UNIQUE INDEX schools_moe_registration_no_unique ON schools(moe_registration_no)');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL: the FK must go before its column can be dropped. The index
        // check keeps this rerunnable after a partially failed rollback.
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        if (Schema::hasIndex('schools', 'schools_subdomain_unique')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropUnique(['subdomain']);
            });
        }

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['subdomain', 'owner_id']);
        });
    }
};
