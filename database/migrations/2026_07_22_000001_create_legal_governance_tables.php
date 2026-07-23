<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('application_key')->index();
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_type')->index();
            $table->string('category')->index();
            $table->string('visibility')->index();
            $table->string('default_locale', 16)->default('en');
            $table->string('owner_reference')->nullable();
            $table->string('source_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('requires_acceptance_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['application_key', 'slug']);
        });

        Schema::create('legal_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('legal_document_id')->constrained()->restrictOnDelete();
            $table->string('version_label');
            $table->string('locale', 16)->default('en');
            $table->string('jurisdiction')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->longText('markdown_source');
            $table->longText('sanitized_html');
            $table->longText('plain_text');
            $table->char('content_checksum', 64);
            $table->text('change_summary')->nullable();
            $table->boolean('is_material_change')->default(false);
            $table->boolean('requires_reacceptance')->default(false);
            $table->timestampTz('proposed_effective_at')->nullable();
            $table->timestampTz('effective_at')->nullable()->index();
            $table->timestampTz('scheduled_publish_at')->nullable()->index();
            $table->timestampTz('published_at')->nullable()->index();
            $table->timestampTz('superseded_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            foreach (['created_by', 'updated_by', 'submitted_for_review_by', 'approved_by', 'published_by'] as $column) {
                $table->string($column)->nullable();
            }
            $table->foreignId('superseded_by_version_id')->nullable()->constrained('legal_document_versions')->nullOnDelete();
            $table->string('source_import_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['legal_document_id', 'version_label', 'locale', 'jurisdiction'], 'legal_version_identity_unique');
        });

        Schema::create('legal_artifacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_version_id')->constrained()->restrictOnDelete();
            $table->string('artifact_type')->index();
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('byte_size');
            $table->string('checksum_algorithm', 16)->default('sha256');
            $table->char('checksum', 64);
            $table->string('renderer_name');
            $table->string('renderer_version')->nullable();
            $table->timestampTz('generated_at');
            $table->string('generated_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['legal_document_version_id', 'artifact_type']);
        });

        Schema::create('legal_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_version_id')->constrained()->restrictOnDelete();
            $table->string('review_type')->index();
            $table->string('reviewer_type');
            $table->string('reviewer_id');
            $table->string('decision')->index();
            $table->text('comments')->nullable();
            $table->char('reviewed_checksum', 64);
            $table->timestampTz('reviewed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_workflows', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('application_key')->index();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type')->index();
            $table->string('subject_type')->nullable();
            $table->string('audience')->index();
            $table->string('status')->default('draft')->index();
            $table->integer('priority')->default(0);
            $table->string('blocking_behavior')->default('notice_only');
            $table->timestampTz('effective_from')->nullable();
            $table->timestampTz('effective_until')->nullable();
            $table->json('configuration')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['application_key', 'slug']);
        });

        Schema::create('legal_workflow_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('version_selection_rule');
            $table->string('specific_version')->nullable();
            $table->string('acceptance_type');
            $table->boolean('is_required')->default(true);
            $table->string('blocking_behavior')->default('notice_only');
            $table->json('presentation_rule')->nullable();
            $table->json('audience_rule')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->unique(['legal_workflow_id', 'legal_document_id']);
        });

        Schema::create('legal_obligations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('legal_workflow_id')->constrained()->restrictOnDelete();
            $table->foreignId('legal_document_version_id')->constrained()->restrictOnDelete();
            $table->string('actor_type');
            $table->string('actor_id');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('organisation_type')->nullable();
            $table->string('organisation_id')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestampTz('required_at');
            $table->timestampTz('due_at')->nullable()->index();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('waived_at')->nullable();
            $table->string('waived_by')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->string('blocking_behavior');
            $table->string('source_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['legal_workflow_id', 'legal_document_version_id', 'actor_type', 'actor_id', 'organisation_type', 'organisation_id'], 'legal_obligation_dedupe');
            $table->index(['actor_type', 'actor_id', 'status']);
            $table->index(['organisation_type', 'organisation_id', 'status']);
        });

        Schema::create('legal_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('legal_document_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('legal_workflow_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('legal_obligation_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('actor_type');
            $table->string('actor_id');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('organisation_type')->nullable();
            $table->string('organisation_id')->nullable();
            $table->string('acceptance_type')->index();
            $table->string('status')->index();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('declined_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->string('acceptance_method');
            $table->text('acceptance_statement');
            $table->string('locale', 16);
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_reference')->nullable();
            $table->string('request_reference')->nullable();
            $table->char('presented_checksum', 64);
            $table->char('manifest_checksum', 64);
            $table->char('evidence_checksum', 64)->unique();
            $table->json('evidence');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['actor_type', 'actor_id']);
            $table->index(['organisation_type', 'organisation_id']);
        });

        Schema::create('legal_manifests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('manifest_type')->index();
            $table->string('schema_version');
            $table->string('application_key')->index();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('organisation_type')->nullable();
            $table->string('organisation_id')->nullable();
            $table->foreignId('legal_workflow_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('canonical_json');
            $table->string('checksum_algorithm', 16)->default('sha256');
            $table->char('checksum', 64)->unique();
            $table->timestampTz('generated_at');
            $table->string('generated_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('legal_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('event_type')->index();
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->foreignId('legal_document_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('legal_document_version_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('legal_workflow_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('legal_acceptance_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('legal_manifest_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestampTz('occurred_at')->index();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_reference')->nullable();
            $table->text('summary');
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('legal_placeholders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_version_id')->constrained()->cascadeOnDelete();
            $table->string('placeholder');
            $table->text('context')->nullable();
            $table->string('severity')->default('error');
            $table->boolean('release_blocking')->default(true)->index();
            $table->string('assigned_owner')->nullable();
            $table->string('status')->default('open')->index();
            $table->text('resolution')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamps();
            $table->index(['legal_document_version_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['legal_placeholders', 'legal_audit_events', 'legal_manifests', 'legal_acceptances', 'legal_obligations', 'legal_workflow_requirements', 'legal_workflows', 'legal_reviews', 'legal_artifacts', 'legal_document_versions', 'legal_documents'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
