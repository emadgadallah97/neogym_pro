<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrShiftsTable extends Migration
{
    public function up()
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 190);

            // أوقات الدوام
            $table->time('start_time');
            $table->time('end_time');

            // إعدادات التأخير/الحساب
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->decimal('min_half_hours', 4, 2)->default(4.00);
            $table->decimal('min_full_hours', 4, 2)->default(8.00);

            // أيام العمل (0/1) — افتراضيًا: الأحد إلى الخميس (مناسب لمصر)
            $table->boolean('sun')->default(1);
            $table->boolean('mon')->default(1);
            $table->boolean('tue')->default(1);
            $table->boolean('wed')->default(1);
            $table->boolean('thu')->default(1);
            $table->boolean('fri')->default(0);
            $table->boolean('sat')->default(0);

            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('user_add')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hr_shifts');
    }
}
