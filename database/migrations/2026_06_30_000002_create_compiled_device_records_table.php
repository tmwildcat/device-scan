<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compiled_device_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('device_datasheet_id')->constrained('device_datasheets')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->string('device_type');
            $table->string('manufacturer')->nullable()->index();
            $table->string('series')->nullable()->index();
            $table->string('family')->nullable()->index();
            $table->string('technology')->nullable()->index();
            $table->string('model_series')->nullable()->index();
            $table->string('model_name')->nullable()->index();
            $table->string('display_name')->nullable()->index();
            $table->unsignedInteger('power_class_w')->nullable()->index();
            $table->decimal('power_class_kw', 10, 3)->nullable()->index();
            $table->string('status')->default('compiled')->index();
            $table->string('compiled_disk');
            $table->string('compiled_path');
            $table->string('compiled_sha256', 64);
            $table->string('compiler_version')->nullable();
            $table->string('validation_grade')->nullable();
            $table->unsignedTinyInteger('validation_score')->nullable();
            $table->string('validation_status')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'device_type', 'status']);
            $table->unique(['compiled_disk', 'compiled_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compiled_device_records');
    }
};
