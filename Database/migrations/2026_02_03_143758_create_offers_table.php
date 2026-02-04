<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            $table->json('name')->nullable();
            $table->json('description')->nullable();

            $table->enum('applies_to', ['any', 'subscription', 'sale', 'service'])->default('subscription');

            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);

            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();

            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();

            $table->enum('status', ['active', 'disabled'])->default('active');

            // Useful for tie-breaking when two discounts are identical
            $table->unsignedInteger('priority')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
            $table->index(['applies_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
