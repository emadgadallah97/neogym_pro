<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_subscriptions', function (Blueprint $table) {
            $table->id();

            // الربط الأساسي
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('subscriptions_plan_id');
            $table->unsignedBigInteger('subscriptions_type_id')->nullable();

            // Snapshot من الخطة
            $table->string('plan_code')->nullable();
            $table->json('plan_name')->nullable(); // لو حابب نخزن JSON، أو نخليه string
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('sessions_count')->nullable();

            // مدرب رئيسي / خطة مع مدرب
            $table->boolean('with_trainer')->default(false);
            $table->unsignedBigInteger('main_trainer_id')->nullable(); // FK إلى employees
            $table->unsignedInteger('sessions_included')->nullable();
            $table->unsignedInteger('sessions_remaining')->nullable();

            // التواريخ والحالة
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'expired', 'frozen', 'cancelled', 'pending'])
                  ->default('active');

            // الصلاحية على الفروع
            $table->boolean('allow_all_branches')->default(false);

            // قناة الاشتراك (مصدر العملية)
            $table->string('source', 50)->nullable(); // website / reception / mobile / ...

            // الأسعار والخصومات
            $table->decimal('price_plan', 10, 2)->default(0);          // سعر الخطة
            $table->decimal('price_pt_addons', 10, 2)->default(0);     // مجموع PT addons
            $table->decimal('discount_offer_amount', 10, 2)->default(0);
            $table->decimal('discount_coupon_amount', 10, 2)->default(0);
            $table->decimal('total_discount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);        // النهائي المستحق

            // العروض والكوبونات
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();

            // الموظف والعمولة
            $table->unsignedBigInteger('sales_employee_id')->nullable(); // موظف المبيعات
            $table->decimal('commission_base_amount', 10, 2)->nullable();
            $table->enum('commission_value_type', ['percent', 'fixed'])->nullable();
            $table->decimal('commission_value', 10, 2)->nullable();      // 5% أو 100
            $table->decimal('commission_amount', 10, 2)->nullable();

            // تدقيق
            $table->unsignedBigInteger('user_add')->nullable();
            $table->unsignedBigInteger('user_update')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FKs أساسية
            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('subscriptions_plan_id')->references('id')->on('subscriptions_plans');
            $table->foreign('subscriptions_type_id')->references('id')->on('subscriptions_types');
            $table->foreign('main_trainer_id')->references('id')->on('employees');
            $table->foreign('sales_employee_id')->references('id')->on('employees');
            $table->foreign('offer_id')->references('id')->on('offers');
            $table->foreign('coupon_id')->references('id')->on('coupons');

            $table->index(['member_id', 'branch_id']);
            $table->index(['status', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_subscriptions');
    }
};
