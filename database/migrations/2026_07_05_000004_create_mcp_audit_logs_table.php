<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('internal_application_id')->nullable()->constrained('internal_applications')->nullOnDelete();
            $table->string('tool_name')->nullable()->index();
            $table->string('action')->default('call')->index();
            $table->string('status')->default('received')->index();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->json('input_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['internal_application_id', 'created_at']);
            $table->index(['tool_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_audit_logs');
    }
};
