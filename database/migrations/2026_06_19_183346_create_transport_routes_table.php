<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('pickup_points')->comment('array of stop names');
            $table->string('vehicle_registration', 20)->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->unsignedInteger('capacity')->default(50);
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 25)->nullable();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_routes');
    }
};
