<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // Allow negative grade numbers for pre-primary classes
            // (e.g. Baby -3, Middle -2, Pre-Grade -1).
            $table->tinyInteger('grade_number')->change();
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->tinyInteger('grade_number')->unsigned()->change();
        });
    }
};
