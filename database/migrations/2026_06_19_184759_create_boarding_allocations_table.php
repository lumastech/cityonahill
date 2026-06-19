<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained('pupils')->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained('beds')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->date('allocated_date');
            $table->date('vacated_date')->nullable();
            $table->decimal('fee_amount', 8, 2);
            $table->enum('status', ['active', 'vacated', 'suspended'])->default('active');
            $table->timestamps();

            $table->unique(['school_id', 'pupil_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_allocations');
    }
};
