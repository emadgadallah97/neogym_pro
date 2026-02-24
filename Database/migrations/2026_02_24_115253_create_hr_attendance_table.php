<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('device_id')->nullable();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('total_hours', 4, 2)->nullable();
            $table->enum('status', [
                'present',
                'absent',
                'late',
                'half_day',
                'leave',
            ])->default('present');
            $table->enum('source', [
                'fingerprint',
                'manual',
                'system',
            ])->default('manual');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_add')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('cascade');

            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');

            $table->foreign('device_id')
                  ->references('id')->on('hr_devices')
                  ->onDelete('set null');

            // منع تكرار الحضور لنفس الموظف في نفس اليوم
            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance');
    }
};
