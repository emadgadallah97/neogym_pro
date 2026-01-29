<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();

            // Translatable (ar/en) - stored as JSON
            $table->json('name');

            // Optional unique code for integration / reference
            $table->string('code', 50)->nullable()->unique();

            // Basic description (optional)
            $table->text('description')->nullable();

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
        Schema::dropIfExists('jobs');
    }
};
