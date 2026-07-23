<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->tinyInteger('grade_number');
            $table->enum('level', ['ece', 'primary', 'junior_secondary', 'senior_secondary']);
            $table->tinyInteger('is_ecz_year')->default(0);
            $table->tinyInteger('order_index');
            $table->timestamps();

            $table->unique(['school_id', 'grade_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
