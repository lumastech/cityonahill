<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_application_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('school_applications')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users');
            // submitted | resubmitted | approved | needs_info | rejected
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_application_logs');
    }
};
