<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('users');
            $table->string('status')->default('pending'); // pending | needs_info | approved | rejected

            // School details
            $table->string('school_name');
            $table->string('subdomain')->unique();
            $table->string('type');     // day | boarding | day_and_boarding
            $table->string('level');    // primary | secondary | combined
            $table->string('province');
            $table->string('district');
            $table->string('address')->nullable();
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->string('moe_registration_no')->nullable();
            $table->string('headteacher_name');

            // Modules the school wants enabled (JSON array of module keys)
            $table->json('modules_config')->nullable();

            // Mobile money number for IzbPay activation billing
            $table->string('mobile_money_number')->nullable();

            // Review info
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_applications');
    }
};
