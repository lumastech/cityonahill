<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20);
            $table->tinyInteger('number');
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('is_current')->default(0);
            $table->date('ca_deadline')->nullable();
            $table->date('exam_start')->nullable();
            $table->date('exam_end')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'academic_year_id', 'number']);
        });

        // Backfill FK constraint for timetable_slots.term_id
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->foreign('term_id')->references('id')->on('terms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
        });

        Schema::dropIfExists('terms');
    }
};
