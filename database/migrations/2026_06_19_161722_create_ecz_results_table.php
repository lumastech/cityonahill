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
        Schema::create('ecz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('ecz_candidates')->cascadeOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->string('raw_result_file', 255)->nullable();
            $table->enum('entry_method', ['manual', 'bulk_upload'])->default('manual');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecz_results');
    }
};
