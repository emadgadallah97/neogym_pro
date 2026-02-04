<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Coupon -> Plans
        Schema::create('coupon_subscriptions_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('subscriptions_plan_id');

            $table->timestamps();

            $table->unique(['coupon_id', 'subscriptions_plan_id'], 'coupon_plan_unique');

            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
            $table->foreign('subscriptions_plan_id')->references('id')->on('subscriptions_plans')->cascadeOnDelete();
        });

        // Coupon -> Types
        Schema::create('coupon_subscriptions_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('subscriptions_type_id');

            $table->timestamps();

            $table->unique(['coupon_id', 'subscriptions_type_id'], 'coupon_type_unique');

            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
            $table->foreign('subscriptions_type_id')->references('id')->on('subscriptions_types')->cascadeOnDelete();
        });

        // Coupon -> Durations
        Schema::create('coupon_durations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');

            $table->unsignedInteger('duration_value');
            $table->enum('duration_unit', ['day', 'month', 'year'])->default('month');

            $table->timestamps();

            $table->unique(['coupon_id', 'duration_value', 'duration_unit'], 'coupon_duration_unique');

            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_durations');
        Schema::dropIfExists('coupon_subscriptions_types');
        Schema::dropIfExists('coupon_subscriptions_plans');
    }
};
