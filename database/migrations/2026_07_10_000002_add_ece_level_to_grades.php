<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite enforces enums via a CHECK constraint baked in at table creation;
        // fresh databases pick up 'ece' from the updated create_grades_table migration.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE grades MODIFY level ENUM('ece', 'primary', 'junior_secondary', 'senior_secondary') NOT NULL");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE grades MODIFY level ENUM('primary', 'junior_secondary', 'senior_secondary') NOT NULL");
        }
    }
};
