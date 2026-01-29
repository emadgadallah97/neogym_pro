<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nationalities_settings', function (Blueprint $table) {
            $table->id();

            // name مترجم (Spatie Translatable)
            $table->json('name');

            // افتراضي أو لا
            $table->boolean('default')->default(false);

            // العلاقة مع الدول
            $table->unsignedBigInteger('id_country');

            // الحالة
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key
            $table->foreign('id_country')
                  ->references('id')
                  ->on('countries')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nationalities_settings');
    }
};
