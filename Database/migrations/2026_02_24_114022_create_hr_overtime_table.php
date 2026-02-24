<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_overtime', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('date');
            $table->decimal('hours', 4, 2);
            $table->decimal('hour_rate', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->date('applied_month')->comment('YYYY-MM-01');
            $table->enum('status', ['pending', 'approved', 'applied'])->default('pending');
            $table->unsignedBigInteger('payroll_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_add')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('payroll_id')->references('id')->on('hr_payrolls')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_overtime');
    }
};
