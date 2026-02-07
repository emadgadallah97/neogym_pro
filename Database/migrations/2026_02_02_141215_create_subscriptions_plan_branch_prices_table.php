<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions_plan_branch_prices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('subscriptions_plan_id');
            $table->unsignedBigInteger('branch_id');

            $table->decimal('price_without_trainer', 10, 2)->default(0);

            // uniform | per_trainer | exceptions
            $table->string('trainer_pricing_mode')->nullable();

            $table->decimal('trainer_uniform_price', 10, 2)->nullable();
            $table->decimal('trainer_default_price', 10, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscriptions_plan_id', 'branch_id'], 'plan_branch_price_unique');

            $table->index('subscriptions_plan_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions_plan_branch_prices');
    }
};
