<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('members', function (Blueprint $table) {
        $table->enum('type', ['member', 'prospect'])
              ->default('member')
              ->after('status')
              ->comment('member=عضو فعلي | prospect=عضو محتمل');
    });
}

public function down(): void
{
    Schema::table('members', function (Blueprint $table) {
        $table->dropColumn('type');
    });
}

};
