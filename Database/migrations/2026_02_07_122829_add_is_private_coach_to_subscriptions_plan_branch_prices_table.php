<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions_plan_branch_prices', function (Blueprint $table) {
            $table->boolean('is_private_coach')->default(0)->after('price_without_trainer');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions_plan_branch_prices', function (Blueprint $table) {
            $table->dropColumn('is_private_coach');
        });
    }
};
