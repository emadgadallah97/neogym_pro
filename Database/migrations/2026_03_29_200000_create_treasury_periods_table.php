<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreasuryPeriodsTable extends Migration
{
    public function up()
    {
        Schema::create('treasury_periods', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');

            $table->string('name', 150);
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->decimal('handed_over',     12, 2)->nullable();
            $table->decimal('carried_forward', 12, 2)->nullable();

            $table->enum('status', ['open', 'closed'])->default('open');

            $table->unsignedBigInteger('opened_by');
            $table->foreign('opened_by')->references('id')->on('users');

            $table->unsignedBigInteger('closed_by')->nullable();
            $table->foreign('closed_by')->references('id')->on('users');

            $table->timestamp('closed_at')->nullable();
            $table->text('close_notes')->nullable();

            $table->timestamps();

            // Only one open period per branch
            $table->index(['branch_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('treasury_periods');
    }
}
