<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('device_user_id', 50)
                  ->nullable()
                  ->default(null)
                  ->after('id') // ✅ غيّر 'id' لأي حقل تريد الإضافة بعده
                  ->comment('رقم العضو في جهاز البصمة / بصمة الوجه');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('device_user_id');
        });
    }
};
