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
        Schema::create('ecz_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained()->cascadeOnDelete();
            $table->year('exam_year');
            $table->tinyInteger('grade_level')->comment('7, 9, or 12');
            $table->string('index_number', 30)->nullable()->unique();
            $table->string('centre_number', 20)->nullable();
            $table->enum('registration_status', ['pending', 'submitted', 'confirmed', 'withdrawn'])->default('pending');
            $table->string('division', 10)->nullable()->comment('Calculated after results e.g. Div 1');
            $table->integer('total_points')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'pupil_id', 'exam_year', 'grade_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecz_candidates');
    }
};
