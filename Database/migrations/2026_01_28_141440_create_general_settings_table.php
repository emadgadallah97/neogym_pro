<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();

            // Translatable (ar/en) - stored as JSON
            $table->json('name')->nullable();

            // Logo path (stored via your trait as a relative path on disk('public'))
            $table->string('logo')->nullable();

            // Optional country from countries table
            $table->unsignedBigInteger('country_id')->nullable();

            // Optional currency from currencies_settings table
            $table->unsignedBigInteger('currency_id')->nullable();

            // Legal / registration
            $table->string('commercial_register')->nullable();
            $table->string('tax_register')->nullable();

            // Basic contact (organization-level; branches will have their own later)
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_add')->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies_settings')->nullOnDelete();

            // Indexes
            $table->index(['country_id']);
            $table->index(['currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
