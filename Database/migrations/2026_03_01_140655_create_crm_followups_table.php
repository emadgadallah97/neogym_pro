<?php
// database/migrations/2026_03_01_000001_create_crm_followups_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->enum('type', ['renewal', 'freeze', 'inactive', 'debt', 'general'])->default('general');
            $table->enum('status', ['pending', 'done', 'cancelled'])->default('pending');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->text('notes')->nullable();
            $table->dateTime('next_action_at')->nullable();
            $table->text('result')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_followups');
    }
};
