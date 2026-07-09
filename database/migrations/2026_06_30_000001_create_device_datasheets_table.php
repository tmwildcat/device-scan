<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_datasheets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_type');
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->string('device_type');
            $table->string('manufacturer')->nullable()->index();
            $table->string('series')->nullable()->index();
            $table->string('product_name')->nullable()->index();
            $table->string('status')->default('uploaded')->index();
            $table->string('datasheet_disk');
            $table->string('datasheet_path');
            $table->string('datasheet_original_filename')->nullable();
            $table->string('datasheet_mime_type')->nullable();
            $table->unsignedBigInteger('datasheet_size_bytes')->nullable();
            $table->string('datasheet_sha256', 64);
            $table->string('compiler_version')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'device_type', 'status']);
            $table->unique(['datasheet_disk', 'datasheet_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_datasheets');
    }
};
