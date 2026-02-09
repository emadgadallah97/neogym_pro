<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('member_subscription_id')->nullable();

            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_method', 30)->nullable(); // cash/card/transfer/instapay...
            $table->enum('status', ['paid', 'pending', 'failed', 'refunded'])->default('paid');
            $table->dateTime('paid_at')->nullable();

            $table->string('reference', 100)->nullable(); // رقم عملية / POS / تحويل
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('user_add')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('member_subscription_id')->references('id')->on('member_subscriptions');

            $table->index(['member_id', 'member_subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
