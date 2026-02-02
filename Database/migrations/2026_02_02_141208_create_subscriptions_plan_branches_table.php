<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions_plan_branches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('subscriptions_plan_id');
            $table->unsignedBigInteger('branch_id');

            $table->timestamps();

            $table->unique(['subscriptions_plan_id', 'branch_id'], 'plan_branch_unique');

            $table->index('subscriptions_plan_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions_plan_branches');
    }
};
