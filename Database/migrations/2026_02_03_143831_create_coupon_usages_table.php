<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('coupon_id');

            // Will be used later with checkout: member, subscription, invoice, payment, etc.
            $table->unsignedBigInteger('member_id')->nullable();

            // Polymorphic reference to whatever entity the coupon was applied to
            $table->string('applied_to_type')->nullable();
            $table->unsignedBigInteger('applied_to_id')->nullable();

            $table->decimal('amount_before', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('amount_after', 10, 2)->nullable();

            $table->dateTime('used_at')->nullable();

            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();

            $table->index(['coupon_id', 'member_id']);
            $table->index(['applied_to_type', 'applied_to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
