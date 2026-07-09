<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_application_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_application_id')->nullable()->constrained('internal_applications')->nullOnDelete();
            $table->string('endpoint');
            $table->string('method', 12);
            $table->string('scope_used')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['internal_application_id', 'created_at']);
            $table->index(['scope_used', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_application_access_logs');
    }
};
