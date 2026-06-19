<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('is_core')->default(1);
            $table->integer('ca_weight')->default(40);
            $table->integer('exam_weight')->default(60);
            $table->timestamps();

            $table->unique(['school_id', 'grade_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_subjects');
    }
};
