<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pupil_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('pupil_id')->constrained('pupils')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->string('pickup_point', 100);
            $table->enum('direction', ['to_school', 'from_school', 'both'])->default('both');
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->decimal('fee_amount', 8, 2)->default(0);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();

            $table->unique(['school_id', 'pupil_id', 'route_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pupil_transport');
    }
};
