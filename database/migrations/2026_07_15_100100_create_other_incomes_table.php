<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->enum('source', ['donation', 'grant', 'uniform_sales', 'book_sales', 'feeding', 'rental', 'fundraising', 'other']);
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->date('received_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'received_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_incomes');
    }
};
