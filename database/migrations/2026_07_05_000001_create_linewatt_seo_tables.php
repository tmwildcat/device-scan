<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_metadata', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_kind')->index();
            $table->string('locale', 8)->default('en')->index();
            $table->string('slug')->index();
            $table->string('canonical_path')->unique();
            $table->string('canonical_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('robots')->default('index,follow');
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->boolean('structured_data_enabled')->default(true);
            $table->boolean('indexable')->default(true)->index();
            $table->decimal('priority', 2, 1)->default(0.5);
            $table->string('change_frequency')->default('weekly');
            $table->string('status')->default('draft')->index();
            $table->text('alt_text')->nullable();
            $table->string('image_title')->nullable();
            $table->text('image_caption')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'locale']);
            $table->index(['entity_kind', 'status', 'indexable']);
        });

        Schema::create('seo_redirects', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_path')->unique();
            $table->string('target_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->boolean('active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('kind')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('taxonomy_type')->nullable()->index();
            $table->string('taxonomy_value')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_landing_pages');
        Schema::dropIfExists('seo_redirects');
        Schema::dropIfExists('seo_metadata');
    }
};
