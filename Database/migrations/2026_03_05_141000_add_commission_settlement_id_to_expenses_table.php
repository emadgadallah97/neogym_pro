<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('commission_settlement_id')->nullable()->after('notes');
            $table->index('commission_settlement_id');
            $table->foreign('commission_settlement_id')->references('id')->on('commission_settlements');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['commission_settlement_id']);
            $table->dropIndex(['commission_settlement_id']);
            $table->dropColumn('commission_settlement_id');
        });
    }
};
