<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('month')->comment('YYYY-MM-01');
            $table->decimal('base_salary', 10, 2);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('allowances_amount', 10, 2)->default(0);
            $table->decimal('advances_deduction', 10, 2)->default(0);
            $table->decimal('deductions_amount', 10, 2)->default(0);
            $table->decimal('gross_salary', 10, 2)
                  ->comment('base_salary + overtime_amount + allowances_amount');
            $table->decimal('net_salary', 10, 2)
                  ->comment('gross_salary - advances_deduction - deductions_amount');
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_add')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unique(['employee_id', 'month']); // منع تكرار الراتب لنفس الشهر
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payrolls');
    }
};
