<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_supporting_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('manufacturer_company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_datasheet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('compiled_device_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supporting_document_scope')->default('company')->index();
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->string('related_label')->nullable();
            $table->string('model_name')->nullable();
            $table->string('revision')->nullable();
            $table->string('language')->nullable();
            $table->string('status')->default('draft');
            $table->string('document_disk')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_original_filename')->nullable();
            $table->string('document_mime_type')->nullable();
            $table->unsignedBigInteger('document_size_bytes')->nullable();
            $table->string('document_sha256', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['manufacturer_company_id', 'supporting_document_scope']);
            $table->index(['device_datasheet_id', 'supporting_document_scope']);
            $table->index('compiled_device_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_supporting_documents');
    }
};
