<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('service_key')->unique();
            $table->string('service_type')->index();
            $table->string('status')->default('active')->index();
            $table->string('environment')->default('local')->index();
            $table->text('description')->nullable();
            $table->foreignId('allowed_internal_application_id')->nullable()->constrained('internal_applications')->nullOnDelete();
            $table->json('required_scopes')->nullable();
            $table->string('endpoint_url')->nullable();
            $table->string('health_check_url')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->text('last_status_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['service_type', 'environment', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_services');
    }
};
