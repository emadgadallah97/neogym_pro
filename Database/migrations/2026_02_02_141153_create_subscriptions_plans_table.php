<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions_plans', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('code')->unique(); // starts from 100 (generated in controller)

            $table->unsignedBigInteger('subscriptions_type_id');
            $table->json('name');

            $table->string('sessions_period_type');
            $table->string('sessions_period_other_label')->nullable();

            $table->unsignedInteger('sessions_count');
            $table->unsignedInteger('duration_days');

            $table->json('allowed_training_days');

            $table->boolean('allow_guest')->default(0);
            $table->unsignedInteger('guest_people_count')->nullable();
            $table->unsignedInteger('guest_times_count')->nullable();
            $table->json('guest_allowed_days')->nullable();

            $table->boolean('notify_before_end')->default(0);
            $table->unsignedInteger('notify_days_before_end')->nullable();

            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('status')->default(1);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('subscriptions_type_id');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions_plans');
    }
};
