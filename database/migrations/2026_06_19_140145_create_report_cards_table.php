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
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained()->cascadeOnDelete();
            $table->text('class_teacher_comment')->nullable();
            $table->text('headteacher_comment')->nullable();
            $table->integer('attendance_days')->nullable();
            $table->integer('attendance_present')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['school_id', 'pupil_id', 'term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
