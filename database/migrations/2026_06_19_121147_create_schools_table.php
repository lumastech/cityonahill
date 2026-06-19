<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->enum('type', ['government', 'private', 'mission', 'grant-aided']);
            $table->enum('level', ['primary', 'secondary', 'basic', 'combined'])
                ->comment('combined = grades 1–12');
            $table->string('province', 50);
            $table->string('district', 50);
            $table->text('address');
            $table->string('phone', 25);
            $table->string('email', 128)->nullable();
            $table->string('website')->nullable();
            $table->string('moe_registration_no', 50)->nullable()->unique();
            $table->foreignId('headteacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->year('established_year')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
