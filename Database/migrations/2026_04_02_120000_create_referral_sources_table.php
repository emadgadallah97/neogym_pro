<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_sources', function (Blueprint $table) {
            $table->id();

            $table->json('name');

            $table->boolean('status')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('useradd')->nullable();
            $table->unsignedBigInteger('userupdate')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_sources');
    }
};
