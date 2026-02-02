<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions_plan_branch_coach_prices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('subscriptions_plan_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id');

            $table->boolean('is_included')->default(1);
            $table->decimal('price', 10, 2)->nullable(); // used in per_trainer or exceptions overrides

            $table->timestamps();
            $table->softDeletes();

            // Composite unique (اسمك الحالي قصير ومناسب)
            $table->unique(
                ['subscriptions_plan_id', 'branch_id', 'employee_id'],
                'plan_branch_coach_unique'
            );

            // Use short explicit index names to avoid MySQL 64-char limit
            $table->index('subscriptions_plan_id', 'spbcp_plan_id_idx');
            $table->index('branch_id', 'spbcp_branch_id_idx');
            $table->index('employee_id', 'spbcp_emp_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions_plan_branch_coach_prices');
    }
};
