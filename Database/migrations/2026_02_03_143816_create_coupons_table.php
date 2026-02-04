<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('code', 60)->unique();

            $table->json('name')->nullable();
            $table->json('description')->nullable();

            $table->enum('applies_to', ['any', 'subscription', 'sale', 'service'])->default('subscription');

            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);

            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();

            $table->unsignedInteger('max_uses_total')->nullable();
            $table->unsignedInteger('max_uses_per_member')->nullable();

            // Optional restriction: coupon only valid for specific member
            $table->unsignedBigInteger('member_id')->nullable();

            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();

            $table->enum('status', ['active', 'disabled'])->default('active');

            $table->unsignedBigInteger('created_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
            $table->index(['applies_to']);
            $table->index(['member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
