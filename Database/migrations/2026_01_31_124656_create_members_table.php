<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->string('member_code')->unique()->nullable();

            $table->unsignedBigInteger('branch_id');

            $table->string('first_name');
            $table->string('last_name');

            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();

            $table->string('phone')->nullable(false);
            $table->string('phone2')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();

            $table->text('address')->nullable(false);
            $table->unsignedBigInteger('id_government')->nullable();
            $table->unsignedBigInteger('id_city')->nullable();
            $table->unsignedBigInteger('id_area')->nullable();

            $table->date('join_date')->nullable();

            // active | inactive | frozen
            $table->string('status')->default('active');

            // Freeze window
            $table->date('freeze_from')->nullable();
            $table->date('freeze_to')->nullable();

            // Membership / body
            $table->decimal('height', 6, 2)->nullable();
            $table->decimal('weight', 6, 2)->nullable();

            // Medical
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();

            $table->text('notes')->nullable();

            // Photo path (public URL path like: attachments/...)
            $table->string('photo')->nullable();

            // Audit (مثل user_add الموجود عندك)
            $table->unsignedBigInteger('user_add')->nullable();
            $table->unsignedBigInteger('user_update')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
