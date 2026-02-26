<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_payrolls')) return;

        // MySQL enum alter
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {

            // لو كان في بيانات قديمة (احتياطي)
            DB::statement("UPDATE hr_payrolls SET payment_method = 'cheque' WHERE payment_method = 'check'");

            DB::statement("
                ALTER TABLE hr_payrolls
                MODIFY payment_method ENUM(
                    'cash',
                    'ewallet',
                    'bank_transfer',
                    'instapay',
                    'credit_card',
                    'cheque',
                    'other'
                ) NULL
            ");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_payrolls')) return;

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {

            // رجوع للقيم القديمة
            DB::statement("UPDATE hr_payrolls SET payment_method = 'check' WHERE payment_method = 'cheque'");

            DB::statement("
                ALTER TABLE hr_payrolls
                MODIFY payment_method ENUM('cash', 'bank_transfer', 'check') NULL
            ");
        }
    }
};
