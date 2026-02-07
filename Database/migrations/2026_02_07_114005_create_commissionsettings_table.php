<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();

            // 1 = قبل الخصومات والعروض، 0 = بعد الخصومات والعروض (الصافي)
            $table->boolean('calculate_commission_before_discounts')->default(0);

            $table->timestamps();
        });

        // Singleton record (id = 1)
        DB::table('commission_settings')->insert([
            'id' => 1,
            'calculate_commission_before_discounts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};
