<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite cannot ALTER COLUMN — recreate the table with the updated enum.
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('invoice_payment_links_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->enum('sent_via', ['sms', 'email', 'copy'])->nullable();
            $table->string('sent_to', 100)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO invoice_payment_links_new SELECT * FROM invoice_payment_links');

        Schema::drop('invoice_payment_links');
        Schema::rename('invoice_payment_links_new', 'invoice_payment_links');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('invoice_payment_links_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->enum('sent_via', ['sms', 'email'])->nullable();
            $table->string('sent_to', 100)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });

        DB::statement("INSERT INTO invoice_payment_links_old SELECT * FROM invoice_payment_links WHERE sent_via IN ('sms', 'email')");

        Schema::drop('invoice_payment_links');
        Schema::rename('invoice_payment_links_old', 'invoice_payment_links');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
