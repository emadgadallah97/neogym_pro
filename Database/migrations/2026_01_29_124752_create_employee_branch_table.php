<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_branch', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(['employee_id', 'branch_id']);

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();

            $table->index(['employee_id']);
            $table->index(['branch_id']);
            $table->index(['is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_branch');
    }
};
