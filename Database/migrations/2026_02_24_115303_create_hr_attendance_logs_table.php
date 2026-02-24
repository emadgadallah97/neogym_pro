<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('device_id');
            $table->unsignedBigInteger('attendance_id')->nullable();
            $table->dateTime('punch_time');
            $table->enum('punch_type', [
                'in',
                'out',
                'unknown',
            ])->default('unknown');
            $table->boolean('is_processed')->default(false);
            $table->json('raw_data')->nullable()->comment('البيانات الخام من الجهاز');
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('cascade');

            $table->foreign('device_id')
                  ->references('id')->on('hr_devices')
                  ->onDelete('cascade');

            $table->foreign('attendance_id')
                  ->references('id')->on('hr_attendance')
                  ->onDelete('set null');

            // فهارس للأداء
            $table->index(['employee_id', 'punch_time']);
            $table->index('is_processed');
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_logs');
    }
};
