<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offer -> Branches
        Schema::create('offer_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();

            $table->unique(['offer_id', 'branch_id'], 'offer_branch_unique');

            $table->foreign('offer_id')->references('id')->on('offers')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        // Coupon -> Branches
        Schema::create('coupon_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();

            $table->unique(['coupon_id', 'branch_id'], 'coupon_branch_unique');

            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_branches');
        Schema::dropIfExists('offer_branches');
    }
};
