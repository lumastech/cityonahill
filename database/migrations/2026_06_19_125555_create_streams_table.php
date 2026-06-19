<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20);
            $table->foreignId('class_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            // academic_years created in Module 2 — nullable, no constraint yet
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->integer('capacity')->default(45);
            $table->timestamps();

            $table->unique(['school_id', 'grade_id', 'name', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
