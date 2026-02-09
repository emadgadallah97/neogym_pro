<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number', 50)->unique();

            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('member_subscription_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable(); // currencies_settings

            $table->decimal('subtotal', 10, 2)->default(0);        // قبل الخصم
            $table->decimal('discount_total', 10, 2)->default(0);  // مجموع الخصوم
            $table->decimal('total', 10, 2)->default(0);           // بعد الخصم

            $table->enum('status', ['issued', 'paid', 'cancelled', 'refunded'])->default('issued');
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('paid_at')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_add')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('member_subscription_id')->references('id')->on('member_subscriptions');
            $table->foreign('currency_id')->references('id')->on('currencies_settings');

            $table->index(['member_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
