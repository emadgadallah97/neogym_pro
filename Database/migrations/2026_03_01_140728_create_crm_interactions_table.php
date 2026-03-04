<?php
// database/migrations/2026_03_01_000002_create_crm_interactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->unsignedBigInteger('followup_id')->nullable();
            $table->foreign('followup_id')->references('id')->on('crm_followups')->nullOnDelete();
            $table->enum('channel', ['call', 'whatsapp', 'visit', 'email', 'sms']);
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->text('notes')->nullable();
            $table->enum('result', ['answered', 'no_answer', 'interested', 'not_interested', 'callback'])->nullable();
            $table->dateTime('interacted_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_interactions');
    }
};
