<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('transaction_id');
            $table->string('gateway_status', 20)->nullable()->after('gateway');
            $table->string('payer_phone', 20)->nullable()->after('gateway_status');
        });

        Schema::create('invoice_payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->enum('sent_via', ['sms', 'email'])->nullable();
            $table->string('sent_to', 100)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_links');
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_status', 'payer_phone']);
        });
    }
};
