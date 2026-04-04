<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whats_app_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('template_key', 100)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->string('message_id', 255)->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'status']);
            $table->index('template_key');
            $table->index('related_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whats_app_logs');
    }
};
