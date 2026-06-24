<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE fee_invoices_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            school_id INTEGER NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
            pupil_id INTEGER NOT NULL REFERENCES pupils(id) ON DELETE CASCADE,
            fee_structure_id INTEGER NULL REFERENCES fee_structures(id) ON DELETE CASCADE,
            term_id INTEGER NOT NULL REFERENCES terms(id) ON DELETE CASCADE,
            academic_year_id INTEGER NOT NULL REFERENCES academic_years(id) ON DELETE CASCADE,
            notes TEXT NULL,
            amount DECIMAL(10,2) NOT NULL,
            discount DECIMAL(10,2) NOT NULL DEFAULT 0,
            balance_due DECIMAL(10,2) NOT NULL,
            due_date DATE NULL,
            status TEXT NOT NULL DEFAULT \'unpaid\' CHECK(status IN (\'unpaid\',\'partial\',\'paid\',\'waived\')),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        DB::statement('INSERT INTO fee_invoices_new
            SELECT id, school_id, pupil_id, fee_structure_id, term_id, academic_year_id,
                   NULL, amount, discount, balance_due, due_date, status, created_at, updated_at
            FROM fee_invoices');

        DB::statement('DROP TABLE fee_invoices');
        DB::statement('ALTER TABLE fee_invoices_new RENAME TO fee_invoices');

        // Restore unique index only for non-null fee_structure_id rows
        DB::statement('CREATE UNIQUE INDEX fee_invoices_structured_unique
            ON fee_invoices(school_id, pupil_id, fee_structure_id, term_id)
            WHERE fee_structure_id IS NOT NULL');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE fee_invoices_old (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            school_id INTEGER NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
            pupil_id INTEGER NOT NULL REFERENCES pupils(id) ON DELETE CASCADE,
            fee_structure_id INTEGER NOT NULL REFERENCES fee_structures(id) ON DELETE CASCADE,
            term_id INTEGER NOT NULL REFERENCES terms(id) ON DELETE CASCADE,
            academic_year_id INTEGER NOT NULL REFERENCES academic_years(id) ON DELETE CASCADE,
            amount DECIMAL(10,2) NOT NULL,
            discount DECIMAL(10,2) NOT NULL DEFAULT 0,
            balance_due DECIMAL(10,2) NOT NULL,
            due_date DATE NULL,
            status TEXT NOT NULL DEFAULT \'unpaid\' CHECK(status IN (\'unpaid\',\'partial\',\'paid\',\'waived\')),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        DB::statement('INSERT INTO fee_invoices_old
            SELECT id, school_id, pupil_id, fee_structure_id, term_id, academic_year_id,
                   amount, discount, balance_due, due_date, status, created_at, updated_at
            FROM fee_invoices WHERE fee_structure_id IS NOT NULL');

        DB::statement('DROP TABLE fee_invoices');
        DB::statement('ALTER TABLE fee_invoices_old RENAME TO fee_invoices');

        DB::statement('CREATE UNIQUE INDEX fee_invoices_school_id_pupil_id_fee_structure_id_term_id_unique
            ON fee_invoices(school_id, pupil_id, fee_structure_id, term_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
