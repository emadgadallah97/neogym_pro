<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrEmployeeShiftsTable extends Migration
{
    public function up()
    {
        Schema::create('hr_employee_shifts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('shift_id');

            // فترة سريان الوردية على الموظف
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('user_add')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'branch_id']);
            $table->index(['shift_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('hr_employee_shifts');
    }
}
