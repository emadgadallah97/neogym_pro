<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_session_pricings', function (Blueprint $table) {
            $table->id();

            // Employee (trainer) id - one pricing per trainer
            $table->unsignedBigInteger('trainer_id')->unique();

            $table->decimal('session_price', 10, 2)->nullable();

            // Who updated (id of authenticated user/admin)
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // FK to employees table (adjust if your employees table name differs)
            $table->foreign('trainer_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_session_pricings');
    }
};
