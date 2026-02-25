<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_overtime', function (Blueprint $table) {
            // مرجع سجل الحضور المصدر (nullable لأن الإدخال اليدوي لا يحتاجه)
            $table->unsignedBigInteger('attendance_id')
                  ->nullable()
                  ->after('branch_id');

            // مصدر السجل: manual (يدوي) أو attendance (تلقائي من الحضور)
            $table->enum('source', ['manual', 'attendance'])
                  ->default('manual')
                  ->after('notes');

            $table->foreign('attendance_id')
                  ->references('id')
                  ->on('hr_attendance')
                  ->nullOnDelete(); // لو حُذف سجل الحضور لا يُحذف الـ overtime
        });
    }

    public function down(): void
    {
        Schema::table('hr_overtime', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
            $table->dropColumn(['attendance_id', 'source']);
        });
    }
};
