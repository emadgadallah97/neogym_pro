<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_subscription_pt_addons', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_subscription_id');
            $table->unsignedBigInteger('trainer_id'); // employee_id (is_coach=1)

            $table->decimal('session_price', 10, 2)->default(0);
            $table->unsignedInteger('sessions_count')->default(0);
            $table->unsignedInteger('sessions_remaining')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_subscription_id')->references('id')->on('member_subscriptions')->cascadeOnDelete();
            $table->foreign('trainer_id')->references('id')->on('employees');

            $table->index(['member_subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_subscription_pt_addons');
    }
};
