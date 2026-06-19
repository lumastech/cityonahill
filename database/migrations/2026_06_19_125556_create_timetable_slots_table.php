<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->comment('1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri');
            $table->tinyInteger('period_number');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            // term_id — nullable, FK added when terms table exists (Module 2)
            $table->unsignedBigInteger('term_id')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'stream_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
