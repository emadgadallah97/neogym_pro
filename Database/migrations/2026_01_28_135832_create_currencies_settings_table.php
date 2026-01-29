<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies_settings', function (Blueprint $table) {
            $table->id();

            // name مترجم (Spatie Translatable)
            $table->json('name');

            // هل العملة افتراضية
            $table->boolean('default')->default(false);

            // العلاقة مع جدول countries
            $table->unsignedBigInteger('id_country');

            // حالة العملة
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key (اختياري لكن مُستحسن)
            $table->foreign('id_country')
                  ->references('id')
                  ->on('countries')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies_settings');
    }
};
