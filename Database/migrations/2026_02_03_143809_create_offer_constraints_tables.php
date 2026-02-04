<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offer -> Plans (subscriptions_plans)
        Schema::create('offer_subscriptions_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('subscriptions_plan_id');

            $table->timestamps();

            $table->unique(['offer_id', 'subscriptions_plan_id'], 'offer_plan_unique');

            $table->foreign('offer_id')->references('id')->on('offers')->cascadeOnDelete();
            $table->foreign('subscriptions_plan_id')->references('id')->on('subscriptions_plans')->cascadeOnDelete();
        });

        // Offer -> Types (subscriptions_types)
        Schema::create('offer_subscriptions_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('subscriptions_type_id');

            $table->timestamps();

            $table->unique(['offer_id', 'subscriptions_type_id'], 'offer_type_unique');

            $table->foreign('offer_id')->references('id')->on('offers')->cascadeOnDelete();
            $table->foreign('subscriptions_type_id')->references('id')->on('subscriptions_types')->cascadeOnDelete();
        });

        // Offer -> Durations (days/months/years)
        Schema::create('offer_durations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');

            $table->unsignedInteger('duration_value'); // 1, 3, 6, 12 ...
            $table->enum('duration_unit', ['day', 'month', 'year'])->default('month');

            $table->timestamps();

            $table->unique(['offer_id', 'duration_value', 'duration_unit'], 'offer_duration_unique');

            $table->foreign('offer_id')->references('id')->on('offers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_durations');
        Schema::dropIfExists('offer_subscriptions_types');
        Schema::dropIfExists('offer_subscriptions_plans');
    }
};
