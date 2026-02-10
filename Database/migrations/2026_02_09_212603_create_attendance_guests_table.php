<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceGuestsTable extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_guests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('attendance_id');

            $table->string('guest_name')->nullable();
            $table->string('guest_phone', 50)->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('user_add')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('attendance_id')->references('id')->on('attendances')->cascadeOnDelete();
            $table->index(['attendance_id'], 'att_guests_attendance_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_guests');
    }
}
