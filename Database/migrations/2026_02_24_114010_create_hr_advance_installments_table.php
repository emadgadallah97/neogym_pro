<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_advance_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advance_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('month')->comment('YYYY-MM-01');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->unsignedBigInteger('payroll_id')->nullable();
            $table->date('paid_date')->nullable();
            $table->timestamps();

            $table->foreign('advance_id')->references('id')->on('hr_advances')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('payroll_id')->references('id')->on('hr_payrolls')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_advance_installments');
    }
};
