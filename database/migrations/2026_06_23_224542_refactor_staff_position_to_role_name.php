<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Old position value => Spatie role name
    private const MAP = [
        'headteacher'           => 'headteacher',
        'deputy_headteacher'    => 'deputy-headteacher',
        'class_teacher'         => 'class-teacher',
        'subject_teacher'       => 'subject-teacher',
        'bursar'                => 'finance-officer',
        'librarian'             => 'librarian',
        'boarding_master'       => 'boarding-master',
        'transport_coordinator' => 'transport-coordinator',
        'feeding_coordinator'   => 'feeding-coordinator',
        'admin'                 => 'school-admin',
        'support'               => 'school-admin',
        'counsellor'            => 'school-admin',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // Add a temporary string column alongside the enum column
        Schema::table('staff', function (Blueprint $table) {
            $table->string('position_new')->nullable()->after('employee_no');
        });

        // Migrate existing values to role names
        $cases = collect(self::MAP)
            ->map(fn ($role, $pos) => "WHEN '{$pos}' THEN '{$role}'")
            ->implode(' ');

        DB::statement("UPDATE staff SET position_new = CASE position {$cases} ELSE position END");

        // Drop the old enum column (Laravel handles SQLite table recreation)
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        // Rename the new column to position
        Schema::table('staff', function (Blueprint $table) {
            $table->renameColumn('position_new', 'position');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->string('position_old')->nullable()->after('employee_no');
        });

        // Reverse the role name back to the old enum value (best-effort)
        $reverseCases = collect(self::MAP)
            ->flip()
            // Multiple old values may map to same role; keep the first
            ->unique()
            ->map(fn ($pos, $role) => "WHEN '{$role}' THEN '{$pos}'")
            ->implode(' ');

        DB::statement("UPDATE staff SET position_old = CASE position {$reverseCases} ELSE position END");

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->renameColumn('position_old', 'position');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
