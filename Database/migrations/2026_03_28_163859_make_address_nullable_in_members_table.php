<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // تعديل عمود address ليقبل NULL بدون Doctrine DBAL
        DB::statement('ALTER TABLE members MODIFY address TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE members MODIFY address TEXT NOT NULL');
    }
};
