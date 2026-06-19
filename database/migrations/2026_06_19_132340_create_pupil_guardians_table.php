<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pupil_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pupil_id')->constrained('pupils');
            $table->foreignId('guardian_id')->constrained('guardians');
            $table->tinyInteger('is_primary')->default(0);
            $table->tinyInteger('is_emergency')->default(0);
            $table->tinyInteger('can_pickup')->default(1);
            $table->timestamps();

            $table->unique(['pupil_id', 'guardian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pupil_guardians');
    }
};
