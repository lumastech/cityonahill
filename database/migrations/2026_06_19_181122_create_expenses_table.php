<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['salaries', 'utilities', 'maintenance', 'supplies', 'transport', 'feeding', 'library', 'other']);
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_no', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
