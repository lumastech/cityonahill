<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('feeding_sessions')->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained('pupils')->cascadeOnDelete();
            $table->tinyInteger('served')->default(1);
            $table->timestamps();

            $table->unique(['session_id', 'pupil_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_records');
    }
};
