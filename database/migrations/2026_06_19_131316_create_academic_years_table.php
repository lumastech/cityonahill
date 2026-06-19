<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 10);
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('is_current')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        // Backfill FK constraint for streams.academic_year_id
        Schema::table('streams', function (Blueprint $table) {
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
        });

        Schema::dropIfExists('academic_years');
    }
};
