<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions_plans', function (Blueprint $table) {
            $table->boolean('allow_freeze')->default(0)->after('notify_days_before_end');
            $table->unsignedSmallInteger('max_freeze_days')->nullable()->after('allow_freeze');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions_plans', function (Blueprint $table) {
            $table->dropColumn(['allow_freeze', 'max_freeze_days']);
        });
    }
};
