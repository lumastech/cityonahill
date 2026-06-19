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
        Schema::create('term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained()->cascadeOnDelete();
            $table->decimal('ca_marks', 5, 2)->nullable();
            $table->decimal('exam_marks', 5, 2)->nullable();
            $table->decimal('total_marks', 5, 2)->nullable();
            $table->string('grade_letter', 2)->nullable();
            $table->tinyInteger('points')->nullable()->comment('ECZ-style: 1=best, 9=worst');
            $table->smallInteger('position_in_stream')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->tinyInteger('published')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'pupil_id', 'subject_id', 'term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_results');
    }
};
