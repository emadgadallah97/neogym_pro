<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whats_app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['key' => 'service_url', 'value' => 'http://localhost:3001', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'api_key', 'value' => 'change_this_secret', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'country_code', 'value' => '20', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'bulk_delay', 'value' => '1500', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'timeout', 'value' => '30', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'enabled', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'log_messages', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'max_bulk', 'value' => '50', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'test_phone', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('whats_app_settings')->insert($rows);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whats_app_settings');
    }
};
