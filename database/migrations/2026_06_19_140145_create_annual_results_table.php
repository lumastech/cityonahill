<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('annual_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_marks', 6, 2);
            $table->decimal('average_marks', 5, 2);
            $table->smallInteger('position_in_stream')->nullable();
            $table->foreignId('grade_stream_id')->constrained('streams');
            $table->tinyInteger('promoted')->default(0);
            $table->text('headteacher_comment')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'pupil_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_results');
    }
};
