<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('branchid');
            $table->unsignedBigInteger('income_type_id');

            $table->date('incomedate');
            $table->decimal('amount', 10, 2)->default(0);

            // ثابتة مثل أجزاء أخرى عندك (cash/card/transfer/instapay/...) [file:5]
            $table->string('paymentmethod', 30)->nullable();

            // الموظف المستلم (غير useradd)
            $table->unsignedBigInteger('receivedbyemployeeid')->nullable();

            // بيانات اختيارية (تسجيل يدوي مستقل)
            $table->string('payername', 150)->nullable();
            $table->string('payerphone', 50)->nullable();

            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // سجل الإلغاء داخل نفس الجدول
            $table->boolean('iscancelled')->default(false);
            $table->unsignedBigInteger('usercancel')->nullable();
            $table->dateTime('cancelledat')->nullable();
            $table->text('cancelreason')->nullable();

            $table->unsignedBigInteger('useradd')->nullable();
            $table->unsignedBigInteger('userupdate')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FKs (عدّل أسماء الجداول لو عندك مختلفة)
            $table->foreign('branchid')->references('id')->on('branches');
            $table->foreign('income_type_id')->references('id')->on('income_types');
            $table->foreign('receivedbyemployeeid')->references('id')->on('employees');

            $table->index(['branchid', 'income_type_id']);
            $table->index(['iscancelled', 'incomedate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
