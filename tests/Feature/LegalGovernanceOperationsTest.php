<?php

use App\LegalGovernance\Actions\PublishLegalVersion;
use App\LegalGovernance\Actions\RecordLegalReview;
use App\LegalGovernance\Actions\TransitionLegalVersion;
use App\LegalGovernance\Actions\UpdateLegalDraft;
use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalArtifact;
use App\LegalGovernance\Models\LegalAuditEvent;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalManifest;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalPlaceholder;
use App\LegalGovernance\Models\LegalReview;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Models\LegalWorkflowRequirement;
use App\LegalGovernance\Services\LegalIntegrityVerifier;
use App\LegalGovernance\Services\LegalWorkflowService;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function operationalCounsel(): User
{
    return User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL, 'email_verified_at' => now()]);
}

function operationalVersion(string $status = 'draft', array $attributes = []): LegalDocumentVersion
{
    $document = LegalDocument::query()->create([
        'public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'operations-'.Str::random(8),
        'title' => 'Operational Legal Document', 'document_type' => 'agreement', 'category' => 'user', 'visibility' => 'public',
    ]);

    $checksum = hash('sha256', '# Operational');

    $version = LegalDocumentVersion::query()->create([
        'public_id' => (string) Str::uuid(), 'legal_document_id' => $document->id, 'version_label' => '1.0 Draft',
        'status' => $status, 'markdown_source' => '# Operational', 'sanitized_html' => '<h1>Operational</h1>',
        'plain_text' => 'Operational', 'content_checksum' => $checksum,
        'change_summary' => 'Operational test.', ...$attributes,
        ...(in_array($status, ['approved', 'scheduled'], true) ? ['approved_at' => now(), 'approved_by' => 'fixture', 'approved_checksum' => $checksum, 'approved_metadata' => []] : []),
    ]);
    if (in_array($status, ['approved', 'scheduled'], true)) {
        LegalReview::create(['legal_document_version_id' => $version->id, 'review_type' => 'legal', 'reviewer_type' => User::class, 'reviewer_id' => 'fixture', 'decision' => 'approved', 'reviewed_checksum' => $checksum, 'reviewed_at' => now(), 'metadata' => []]);
    }

    return $version;
}

it('serves every completed operations page through the legal shell with permission enforcement', function (string $route, string $component) {
    $counsel = operationalCounsel();

    $this->actingAs($counsel)->get(route($route))->assertOk()->assertInertia(fn ($page) => $page->component($component));
    $ordinary = User::factory()->create(['role' => LineWattRole::GUEST, 'email_verified_at' => now()]);
    $this->actingAs($ordinary)->get(route($route))->assertForbidden();
})->with([
    ['legal-governance.reviews.index', 'LineWatt/LegalGovernanceOperations'],
    ['legal-governance.publications.index', 'LineWatt/LegalGovernanceOperations'],
    ['legal-governance.workflows.index', 'LineWatt/LegalGovernanceOperations'],
    ['legal-governance.evidence-exports.index', 'LineWatt/LegalGovernanceOperations'],
    ['legal-governance.placeholders.index', 'LineWatt/LegalGovernanceOperations'],
    ['legal-governance.settings', 'LineWatt/LegalGovernanceOperations'],
]);

it('records the exact reviewed checksum and requires the current approval before lifecycle approval', function () {
    $version = operationalVersion();
    $counsel = operationalCounsel();
    $transitions = app(TransitionLegalVersion::class);
    $transitions->submitForReview($version, $counsel);
    $review = app(RecordLegalReview::class)->handle($version->refresh(), 'legal', $counsel, 'approved', 'Reviewed current text.');
    $approved = $transitions->approve($version->refresh(), $counsel, ['legal']);

    expect($review->reviewed_checksum)->toBe($version->content_checksum)
        ->and($approved->status->value)->toBe('approved');
});

it('invalidates checksum-bound approval when changed content returns to draft', function () {
    $version = operationalVersion();
    $counsel = operationalCounsel();
    app(TransitionLegalVersion::class)->submitForReview($version, $counsel);
    app(RecordLegalReview::class)->handle($version->refresh(), 'legal', $counsel, 'changes_requested', 'Correct the text.');
    $oldChecksum = $version->content_checksum;

    $updated = app(UpdateLegalDraft::class)->handle($version->refresh(), '# Changed operational text', 'Requested correction.', $counsel);

    expect($updated->status->value)->toBe('draft')
        ->and($updated->content_checksum)->not->toBe($oldChecksum)
        ->and($updated->reviews()->where('reviewed_checksum', $updated->content_checksum)->exists())->toBeFalse();
});

it('supports cancellation of a publication schedule without publishing content', function () {
    $counsel = operationalCounsel();
    $version = operationalVersion('approved', ['effective_at' => now()->addDays(2)]);
    app(TransitionLegalVersion::class)->schedule($version, $counsel, now()->addDay());

    $this->actingAs($counsel)->delete(route('legal-governance.versions.schedule.cancel', $version), ['reason' => 'Timing changed.'])->assertRedirect();

    expect($version->refresh()->status->value)->toBe('approved')
        ->and($version->schedule_cancelled_at)->not->toBeNull()
        ->and(LegalAuditEvent::where('event_type', 'legal_publication_schedule_cancelled')->exists())->toBeTrue();
});

it('blocks publication until readiness requirements pass and freezes artifacts and manifest', function () {
    Storage::fake('local');
    config(['legal-governance.storage_disk' => 'local']);
    $blocked = operationalVersion('approved', ['effective_at' => now()]);
    LegalPlaceholder::query()->create(['legal_document_version_id' => $blocked->id, 'placeholder' => '[OPEN]', 'status' => 'open', 'severity' => 'error', 'release_blocking' => true]);
    $counsel = operationalCounsel();
    expect(fn () => app(PublishLegalVersion::class)->handle($blocked, $counsel))->toThrow(LogicException::class, 'Release-blocking placeholders remain');

    $version = operationalVersion('approved', ['effective_at' => now()]);
    $published = app(PublishLegalVersion::class)->handle($version, $counsel);

    expect($published->status->value)->toBe('published')
        ->and(LegalArtifact::query()->where('legal_document_version_id', $version->id)->count())->toBeGreaterThanOrEqual(4)
        ->and(LegalManifest::query()->where('manifest_type', 'publication')->count())->toBe(1)
        ->and(LegalAuditEvent::query()->where('event_type', 'legal_version_published')->exists())->toBeTrue();
});

it('creates reacceptance obligations only for configured material changes', function () {
    Storage::fake('local');
    config(['legal-governance.storage_disk' => 'local']);
    $prior = operationalVersion('published', ['published_at' => now()->subDay(), 'effective_at' => now()->subDay()]);
    $workflow = LegalWorkflow::query()->create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'material-change', 'name' => 'Material change', 'trigger_type' => 'material_change', 'audience' => 'registered_users', 'status' => 'active', 'blocking_behavior' => 'next_login_block']);
    LegalWorkflowRequirement::query()->create(['legal_workflow_id' => $workflow->id, 'legal_document_id' => $prior->legal_document_id, 'sequence' => 1, 'version_selection_rule' => 'current_published', 'acceptance_type' => 'clickwrap_acceptance', 'is_required' => true, 'blocking_behavior' => 'next_login_block', 'configuration' => ['statement' => 'I accept.']]);
    LegalAcceptance::query()->create([
        'public_id' => (string) Str::uuid(), 'legal_document_version_id' => $prior->id, 'legal_workflow_id' => $workflow->id,
        'actor_type' => User::class, 'actor_id' => 'subject-1', 'subject_type' => 'user', 'subject_id' => 'subject-1',
        'acceptance_type' => 'clickwrap_acceptance', 'status' => 'accepted', 'accepted_at' => now(), 'acceptance_method' => 'test',
        'acceptance_statement' => 'I accept.', 'locale' => 'en', 'presented_checksum' => $prior->content_checksum,
        'manifest_checksum' => str_repeat('a', 64), 'evidence_checksum' => str_repeat('b', 64), 'evidence' => [],
    ]);
    $replacement = LegalDocumentVersion::query()->create([
        'public_id' => (string) Str::uuid(), 'legal_document_id' => $prior->legal_document_id, 'version_label' => '2.0 Draft', 'status' => 'approved',
        'markdown_source' => '# Material replacement', 'sanitized_html' => '<h1>Material replacement</h1>', 'plain_text' => 'Material replacement',
        'content_checksum' => hash('sha256', '# Material replacement'), 'effective_at' => now(), 'is_material_change' => true,
        'metadata' => ['requires_reacceptance' => true],
    ]);

    $replacement->update(['approved_at' => now(), 'approved_by' => 'fixture', 'approved_checksum' => $replacement->content_checksum, 'approved_metadata' => $replacement->metadata]);
    LegalReview::create(['legal_document_version_id' => $replacement->id, 'review_type' => 'legal', 'reviewer_type' => User::class, 'reviewer_id' => 'fixture', 'decision' => 'approved', 'reviewed_checksum' => $replacement->content_checksum, 'reviewed_at' => now(), 'metadata' => []]);
    app(PublishLegalVersion::class)->handle($replacement, operationalCounsel());

    expect(LegalObligation::where('legal_document_version_id', $replacement->id)->where('actor_id', 'subject-1')->exists())->toBeTrue()
        ->and(LegalAuditEvent::where('event_type', 'legal_reacceptance_obligations_assigned')->exists())->toBeTrue();
});

it('rejects unsafe workflow activation configurations and previews without acceptance side effects', function () {
    $version = operationalVersion('draft');
    $workflow = LegalWorkflow::query()->create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'unsafe', 'name' => 'Unsafe', 'trigger_type' => 'registration', 'audience' => 'registered_users', 'status' => 'draft', 'blocking_behavior' => 'next_login_block']);
    LegalWorkflowRequirement::query()->create(['legal_workflow_id' => $workflow->id, 'legal_document_id' => $version->legal_document_id, 'sequence' => 1, 'version_selection_rule' => 'current_published', 'acceptance_type' => 'clickwrap_acceptance', 'is_required' => true, 'blocking_behavior' => 'next_login_block', 'configuration' => ['statement' => 'I agree.']]);

    expect(app(LegalWorkflowService::class)->validate($workflow))->toContain('Operational Legal Document has no selectable version.');
});

it('edits workflow requirements declaratively and returns the workflow to draft', function () {
    $counsel = operationalCounsel();
    $version = operationalVersion('published', ['published_at' => now(), 'effective_at' => now()]);
    $workflow = LegalWorkflow::query()->create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'requirement-editor', 'name' => 'Requirement editor', 'trigger_type' => 'registration', 'audience' => 'registered_users', 'status' => 'active', 'blocking_behavior' => 'next_login_block']);

    $this->actingAs($counsel)->post(route('legal-governance.workflows.requirements.store', $workflow), [
        'document_id' => $version->document->public_id, 'sequence' => 1, 'version_selection_rule' => 'current_published',
        'specific_version' => null, 'acceptance_type' => 'clickwrap_acceptance', 'is_required' => true,
        'blocking_behavior' => 'next_login_block', 'statement' => 'I accept this governed version.',
    ])->assertRedirect();

    expect($workflow->refresh()->status)->toBe('draft')
        ->and($workflow->requirements()->count())->toBe(1)
        ->and(app(LegalWorkflowService::class)->validate($workflow->refresh()))->toBe([]);
});

it('detects missing or changed retained artifacts without modifying evidence', function () {
    Storage::fake('local');
    config(['legal-governance.storage_disk' => 'local']);
    $version = operationalVersion('approved', ['effective_at' => now()]);
    app(PublishLegalVersion::class)->handle($version, operationalCounsel());
    $artifact = LegalArtifact::query()->firstOrFail();
    Storage::disk('local')->put($artifact->storage_path, 'tampered');

    $result = app(LegalIntegrityVerifier::class)->verify('test');

    expect($result['discrepancies'])->not->toBeEmpty()
        ->and(collect($result['discrepancies'])->pluck('type'))->toContain('artifact_checksum')
        ->and(LegalAuditEvent::query()->where('event_type', 'legal_integrity_verified')->exists())->toBeTrue();
});
