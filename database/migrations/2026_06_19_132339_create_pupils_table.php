<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pupils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('admission_no', 30);
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('other_name', 50)->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->date('dob');
            $table->string('place_of_birth', 100)->nullable();
            $table->string('nationality', 64)->default('Zambian');
            $table->string('religion', 50)->nullable();
            $table->string('tribe', 50)->nullable();
            $table->enum('disability', ['none', 'visual', 'hearing', 'physical', 'intellectual', 'other'])->default('none');
            $table->text('disability_details')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('previous_school', 150)->nullable();
            $table->date('date_of_admission');
            $table->foreignId('grade_id')->constrained('grades');
            $table->foreignId('stream_id')->nullable()->constrained('streams');
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->enum('status', ['active', 'transferred', 'withdrawn', 'completed', 'suspended'])->default('active');
            $table->string('transfer_school', 150)->nullable();
            $table->date('transfer_date')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'admission_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pupils');
    }
};
