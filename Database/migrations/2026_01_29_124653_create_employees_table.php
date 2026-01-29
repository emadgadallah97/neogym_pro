<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Unique code (generated after create)
            $table->string('code', 50)->nullable()->unique();

            // Basic identity (no ar/en)
            $table->string('first_name', 150);
            $table->string('last_name', 150);

            // Job
            $table->unsignedBigInteger('job_id')->nullable();

            // Photo path like: attachments/employees/photo/...
            $table->string('photo')->nullable();

            // Personal
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();

            // Contact
            $table->string('phone_1', 50)->nullable();
            $table->string('phone_2', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email')->nullable();

            // Professional
            $table->string('specialization', 255)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->text('bio')->nullable();

            // Compensation
            $table->enum('compensation_type', ['salary_only', 'commission_only', 'salary_and_commission'])
                ->default('salary_only');

            $table->decimal('base_salary', 12, 2)->nullable();

            // You can use percent and fixed together if needed
            $table->decimal('commission_percent', 5, 2)->nullable(); // 0 - 100
            $table->decimal('commission_fixed', 12, 2)->nullable();

            // Salary transfer
            $table->enum('salary_transfer_method', [
                'cash',
                'ewallet',
                'bank_transfer',
                'instapay',
                'credit_card',
                'cheque',
                'other',
            ])->nullable();

            $table->text('salary_transfer_details')->nullable();

            // System
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('user_add')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('job_id')->references('id')->on('jobs')->nullOnDelete();

            // Indexes
            $table->index(['job_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
