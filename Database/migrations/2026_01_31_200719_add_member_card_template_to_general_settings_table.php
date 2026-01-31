<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('general_settings', 'member_card_template')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('member_card_template', 50)
                ->nullable()
                ->after('notes');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('general_settings', 'member_card_template')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('member_card_template');
        });
    }
};
