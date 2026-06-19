<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('employee_no', 30);
            $table->enum('position', [
                'headteacher', 'deputy_headteacher', 'class_teacher', 'subject_teacher',
                'bursar', 'librarian', 'boarding_master', 'transport_coordinator',
                'feeding_coordinator', 'admin', 'support', 'counsellor',
            ]);
            $table->string('department', 100)->nullable();
            $table->json('subjects_taught')->nullable()->comment('array of subject_ids');
            $table->enum('employment_type', ['permanent', 'contract', 'temporary', 'volunteer']);
            $table->date('employment_date');
            $table->date('end_date')->nullable();
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->string('bank', 100)->nullable();
            $table->string('bank_account', 30)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->string('nrc', 25)->nullable();
            $table->string('tax_id', 25)->nullable();
            $table->string('tpin', 15)->nullable()->comment('Zambia TPIN');
            $table->string('napsa_no', 25)->nullable()->comment('Zambia NAPSA number');
            $table->enum('status', ['active', 'terminated', 'suspended', 'on_leave'])->default('active');
            $table->timestamps();
            $table->unique(['school_id', 'employee_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
