<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('member_id');

            $table->date('attendance_date');
            $table->time('attendance_time')->nullable();
            $table->string('day_key', 20)->nullable(); // monday, tuesday...

            // Subscription references (what we actually deducted from)
            $table->unsignedBigInteger('member_subscription_id')->nullable();
            $table->unsignedBigInteger('pt_addon_id')->nullable();

            // Base + PT flags
            $table->boolean('is_base_deducted')->default(true);
            $table->boolean('is_pt_deducted')->default(false);

            // Sessions audit
            $table->integer('base_sessions_before')->nullable();
            $table->integer('base_sessions_after')->nullable();
            $table->integer('pt_sessions_before')->nullable();
            $table->integer('pt_sessions_after')->nullable();

            // Method + who recorded (manual)
            $table->string('checkin_method', 20)->default('barcode'); // manual|barcode|mobile|other
            $table->unsignedBigInteger('recorded_by')->nullable(); // users.id (manual)

            // Future use
            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('gate_id')->nullable();
            $table->timestamp('check_out_at')->nullable();

            // Cancellation
            $table->boolean('is_cancelled')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();

            // PT refund tracking (cancel PT only)
            $table->timestamp('pt_refunded_at')->nullable();
            $table->unsignedBigInteger('pt_refunded_by')->nullable();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('user_add')->nullable();
            $table->unsignedBigInteger('user_update')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();

            // لو الجداول دي عندك باسم مختلف قولي قبل التشغيل
            $table->foreign('member_subscription_id')->references('id')->on('member_subscriptions')->nullOnDelete();
            $table->foreign('pt_addon_id')->references('id')->on('member_subscription_pt_addons')->nullOnDelete();

            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('pt_refunded_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['branch_id', 'attendance_date'], 'att_branch_date_idx');
            $table->index(['member_id', 'attendance_date'], 'att_member_date_idx');
            $table->index(['member_subscription_id'], 'att_member_sub_idx');

            // ✅ منع حضور أكثر من مرة في اليوم (مع السماح بعد الإلغاء)
            $table->unique(['member_id', 'attendance_date', 'is_cancelled'], 'att_unique_member_day_cancel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
}
