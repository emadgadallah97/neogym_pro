<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_payrolls')) return;

        Schema::table('hr_payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_payrolls', 'salary_transfer_details')) {
                $table->text('salary_transfer_details')->nullable()->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_payrolls')) return;

        Schema::table('hr_payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('hr_payrolls', 'salary_transfer_details')) {
                $table->dropColumn('salary_transfer_details');
            }
        });
    }
};
