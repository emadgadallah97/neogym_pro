<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('renewal_of')->nullable()->after('notes');
            $table->unsignedBigInteger('renewed_to')->nullable()->after('renewal_of');

            $table->foreign('renewal_of')->references('id')->on('member_subscriptions')->nullOnDelete();
            $table->foreign('renewed_to')->references('id')->on('member_subscriptions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['renewal_of']);
            $table->dropForeign(['renewed_to']);
            $table->dropColumn(['renewal_of', 'renewed_to']);
        });
    }
};
