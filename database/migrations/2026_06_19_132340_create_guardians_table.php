<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->enum('relationship', ['father', 'mother', 'guardian', 'grandparent', 'sibling', 'other']);
            $table->string('phone', 25);
            $table->string('phone2', 25)->nullable();
            $table->string('email', 128)->nullable()->unique();
            $table->string('nrc', 25)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('employer', 100)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
