<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('power_search_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scope')->default('all')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('power_search_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('power_search_category_id')->constrained('power_search_categories')->cascadeOnDelete();
            $table->string('label');
            $table->string('slug')->unique();
            $table->string('scope')->default('all')->index();
            $table->string('country')->nullable()->index();
            $table->string('region')->nullable()->index();
            $table->string('subtype')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->string('reference_source')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['power_search_category_id', 'is_active', 'sort_order']);
        });

        Schema::create('power_search_tag_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compiled_device_record_id')->constrained('compiled_device_records')->cascadeOnDelete();
            $table->foreignId('power_search_option_id')->constrained('power_search_options')->cascadeOnDelete();
            $table->string('source')->default('curated')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['compiled_device_record_id', 'power_search_option_id'], 'power_search_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('power_search_tag_assignments');
        Schema::dropIfExists('power_search_options');
        Schema::dropIfExists('power_search_categories');
    }
};
