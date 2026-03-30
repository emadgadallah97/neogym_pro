<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreasuryTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('period_id');
            $table->foreign('period_id')->references('id')->on('treasury_periods');

            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->enum('type', ['in', 'out']);
            $table->decimal('amount', 12, 2);

            // No constrained() — intentional for audit trail integrity
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->boolean('is_reversal')->default(false);

            $table->unsignedBigInteger('reversal_of')->nullable();
            $table->foreign('reversal_of')->references('id')->on('treasury_transactions');

            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');

            $table->timestamps();

            // Performance indexes
            $table->index(['period_id', 'type']);
            $table->index(['source_type', 'source_id']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('treasury_transactions');
    }
}
