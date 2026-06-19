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
        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained()->cascadeOnDelete();
            $table->decimal('marks_obtained', 5, 2);
            $table->string('grade_letter', 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('entered_by')->constrained('users');
            $table->dateTime('entered_at');
            $table->timestamps();
            $table->unique(['assessment_id', 'pupil_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
    }
};
