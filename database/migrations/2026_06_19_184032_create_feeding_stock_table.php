<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('item_name', 100);
            $table->string('unit', 20);
            $table->decimal('quantity_on_hand', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->date('last_restocked_at')->nullable();
            $table->decimal('cost_per_unit', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_stock');
    }
};
