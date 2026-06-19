<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->enum('category', ['core', 'elective', 'language', 'vocational', 'religious', 'physical']);
            $table->tinyInteger('is_zambian_language')->default(0);
            $table->tinyInteger('is_ecz_subject')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
