<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->date('date');
            $table->enum('meal_type', ['breakfast', 'lunch', 'snack']);
            $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete()
                ->comment('null = whole school');
            $table->foreignId('recorded_by')->constrained('users');
            $table->tinyInteger('finalized')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'date', 'meal_type', 'stream_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_sessions');
    }
};
