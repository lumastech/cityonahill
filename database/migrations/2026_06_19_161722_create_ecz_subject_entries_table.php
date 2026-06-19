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
        Schema::create('ecz_subject_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('ecz_candidates')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entered_by')->constrained('users');
            $table->string('predicted_grade', 2)->nullable();
            $table->string('actual_grade', 2)->nullable()->comment('A-F or U');
            $table->tinyInteger('actual_points')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecz_subject_entries');
    }
};
