<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_advances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('monthly_installment', 10, 2);
            $table->unsignedInteger('installments_count');
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2);
            $table->date('request_date');
            $table->date('start_month')->comment('YYYY-MM-01');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])
                  ->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_add')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_advances');
    }
};
