<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('stream_id')->constrained('streams');
            $table->foreignId('term_id')->constrained('terms');
            $table->date('date');
            $table->enum('session_type', ['morning', 'afternoon', 'full_day'])->default('full_day');
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->tinyInteger('finalized')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'stream_id', 'date', 'session_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
