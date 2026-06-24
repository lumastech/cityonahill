<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE boarding_allocations_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            school_id INTEGER NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
            pupil_id INTEGER NOT NULL REFERENCES pupils(id) ON DELETE CASCADE,
            bed_id INTEGER NOT NULL REFERENCES beds(id) ON DELETE CASCADE,
            term_id INTEGER NOT NULL REFERENCES terms(id) ON DELETE CASCADE,
            allocated_date DATE NOT NULL,
            vacated_date DATE NULL,
            fee_amount DECIMAL(8,2) NOT NULL,
            status TEXT NOT NULL DEFAULT \'active\' CHECK(status IN (\'active\',\'vacated\',\'suspended\')),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        DB::statement('INSERT INTO boarding_allocations_new
            SELECT id, school_id, pupil_id, bed_id, term_id,
                   allocated_date, vacated_date, fee_amount, status, created_at, updated_at
            FROM boarding_allocations');

        DB::statement('DROP TABLE boarding_allocations');
        DB::statement('ALTER TABLE boarding_allocations_new RENAME TO boarding_allocations');

        // Only one active allocation per pupil per term — vacated rows are excluded
        DB::statement('CREATE UNIQUE INDEX boarding_allocations_active_unique
            ON boarding_allocations(school_id, pupil_id, term_id)
            WHERE status = \'active\'');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE boarding_allocations_old (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            school_id INTEGER NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
            pupil_id INTEGER NOT NULL REFERENCES pupils(id) ON DELETE CASCADE,
            bed_id INTEGER NOT NULL REFERENCES beds(id) ON DELETE CASCADE,
            term_id INTEGER NOT NULL REFERENCES terms(id) ON DELETE CASCADE,
            allocated_date DATE NOT NULL,
            vacated_date DATE NULL,
            fee_amount DECIMAL(8,2) NOT NULL,
            status TEXT NOT NULL DEFAULT \'active\' CHECK(status IN (\'active\',\'vacated\',\'suspended\')),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        DB::statement('INSERT INTO boarding_allocations_old
            SELECT id, school_id, pupil_id, bed_id, term_id,
                   allocated_date, vacated_date, fee_amount, status, created_at, updated_at
            FROM boarding_allocations');

        DB::statement('DROP TABLE boarding_allocations');
        DB::statement('ALTER TABLE boarding_allocations_old RENAME TO boarding_allocations');

        DB::statement('CREATE UNIQUE INDEX boarding_allocations_school_id_pupil_id_term_id_unique
            ON boarding_allocations(school_id, pupil_id, term_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
