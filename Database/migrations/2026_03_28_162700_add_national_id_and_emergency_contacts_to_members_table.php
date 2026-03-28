<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('national_id', 30)->nullable()->after('email');
            // يُخزَّن كـ JSON: [{name, phone}, {name, phone}, {name, phone}]
            $table->json('emergency_contacts')->nullable()->after('national_id');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['national_id', 'emergency_contacts']);
        });
    }
};
