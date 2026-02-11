<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('branchid');
            $table->unsignedBigInteger('expensestypeid');

            $table->date('expensedate');
            $table->decimal('amount', 12, 2);

            // Recipient info
            $table->string('recipientname', 255);
            $table->string('recipientphone', 50)->nullable();
            $table->string('recipientnationalid', 100)->nullable();

            // Disburser (Employee)
            $table->unsignedBigInteger('disbursedbyemployeeid')->nullable();

            // بيان / وصف المصروف
            $table->text('description')->nullable();

            $table->text('notes')->nullable();

            // Cancelation instead of active/inactive
            $table->boolean('iscancelled')->default(false);
            $table->timestamp('cancelledat')->nullable();
            $table->unsignedBigInteger('cancelledby')->nullable(); // user id

            // Audit
            $table->unsignedBigInteger('useradd')->nullable();
            $table->unsignedBigInteger('userupdate')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branchid', 'expensedate'], 'expenses_branch_date_idx');
            $table->index(['expensestypeid', 'expensedate'], 'expenses_type_date_idx');
            $table->index('iscancelled');

            $table->foreign('branchid')->references('id')->on('branches');
            $table->foreign('expensestypeid')->references('id')->on('expenses_types');

            $table->foreign('disbursedbyemployeeid')->references('id')->on('employees')->nullOnDelete();

            $table->foreign('cancelledby')->references('id')->on('users')->nullOnDelete();
            $table->foreign('useradd')->references('id')->on('users')->nullOnDelete();
            $table->foreign('userupdate')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
