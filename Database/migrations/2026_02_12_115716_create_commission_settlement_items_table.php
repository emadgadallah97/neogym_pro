<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_settlement_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('commission_settlement_id');
            $table->unsignedBigInteger('member_subscription_id');

            // Snapshots للتقارير
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('sales_employee_id')->nullable();

            $table->decimal('commission_base_amount', 10, 2)->nullable();
            $table->string('commission_value_type', 20)->nullable();
            $table->decimal('commission_value', 10, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->default(0);

            $table->dateTime('subscription_created_at')->nullable();

            $table->boolean('is_excluded')->default(false);
            $table->string('exclude_reason', 255)->nullable();

            $table->timestamps();

            $table->index(['commission_settlement_id']);
            $table->index(['member_subscription_id']);
            $table->index(['sales_employee_id']);
            $table->index(['branch_id']);
            $table->index(['is_excluded']);

            $table->foreign('commission_settlement_id')
                ->references('id')->on('commission_settlements')
                ->onDelete('cascade');

            $table->foreign('member_subscription_id')
                ->references('id')->on('member_subscriptions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settlement_items');
    }
};
