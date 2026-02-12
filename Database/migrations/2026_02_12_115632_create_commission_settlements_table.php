<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_settlements', function (Blueprint $table) {
            $table->id();

            $table->date('date_from');
            $table->date('date_to');

            // null = مجمّع لكل الموظفين، أو قيمة = لموظف محدد
            $table->unsignedBigInteger('sales_employee_id')->nullable();

            $table->enum('status', ['draft', 'paid', 'cancelled'])->default('draft');

            $table->decimal('total_commission_amount', 10, 2)->default(0); // included
            $table->decimal('total_excluded_commission_amount', 10, 2)->default(0);
            $table->decimal('total_all_commission_amount', 10, 2)->default(0);

            $table->unsignedInteger('items_count')->default(0); // included
            $table->unsignedInteger('excluded_items_count')->default(0);
            $table->unsignedInteger('all_items_count')->default(0);

            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('user_add')->nullable();
            $table->unsignedBigInteger('user_update')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['date_from', 'date_to']);
            $table->index(['status']);
            $table->index(['sales_employee_id']);
            $table->index(['paid_at']);

            $table->foreign('sales_employee_id')->references('id')->on('employees');
            $table->foreign('paid_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settlements');
    }
};
