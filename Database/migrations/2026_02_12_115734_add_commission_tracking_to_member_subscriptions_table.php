<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            $table->boolean('commission_is_paid')->default(false)->after('commission_amount');
            $table->dateTime('commission_paid_at')->nullable()->after('commission_is_paid');
            $table->unsignedBigInteger('commission_paid_by')->nullable()->after('commission_paid_at');
            $table->unsignedBigInteger('commission_settlement_id')->nullable()->after('commission_paid_by');

            $table->index(['commission_is_paid']);
            $table->index(['commission_paid_at']);
            $table->index(['commission_paid_by']);
            $table->index(['commission_settlement_id']);

            $table->foreign('commission_paid_by')->references('id')->on('users');
            $table->foreign('commission_settlement_id')->references('id')->on('commission_settlements');
        });
    }

    public function down(): void
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['commission_paid_by']);
            $table->dropForeign(['commission_settlement_id']);

            $table->dropColumn([
                'commission_is_paid',
                'commission_paid_at',
                'commission_paid_by',
                'commission_settlement_id',
            ]);
        });
    }
};
