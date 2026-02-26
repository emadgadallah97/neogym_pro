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

            // 1) أنشئ القيد الجديد أولاً (سيكون Index بديل يعتمد عليه FK employee_id)
            $table->unique(['employee_id', 'branch_id', 'month']);

            // 2) بعدها احذف القيد القديم
            $table->dropUnique('hr_payrolls_employee_id_month_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hr_payrolls')) return;

        Schema::table('hr_payrolls', function (Blueprint $table) {

            // رجّع القديم أولاً
            $table->unique(['employee_id', 'month']);

            // ثم احذف الجديد
            $table->dropUnique('hr_payrolls_employee_id_branch_id_month_unique');
        });
    }
};
