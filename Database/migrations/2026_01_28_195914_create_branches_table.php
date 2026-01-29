<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            // Translatable (ar/en) - stored as JSON
            $table->json('name');

            // Basic necessary details
            $table->text('address')->nullable();
            $table->string('phone_1', 50)->nullable();
            $table->string('phone_2', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_add')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
