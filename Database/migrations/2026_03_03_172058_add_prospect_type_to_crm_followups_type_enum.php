<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ أضفنا 'prospect' كنوع جديد (عميل محتمل)
        DB::statement("
            ALTER TABLE crm_followups
            MODIFY COLUMN type ENUM('renewal','freeze','inactive','debt','general','prospect')
            NOT NULL
            DEFAULT 'general'
        ");
    }

    public function down(): void
    {
        // رجوع للوضع السابق بدون prospect
        DB::statement("
            ALTER TABLE crm_followups
            MODIFY COLUMN type ENUM('renewal','freeze','inactive','debt','general')
            NOT NULL
            DEFAULT 'general'
        ");
    }
};
