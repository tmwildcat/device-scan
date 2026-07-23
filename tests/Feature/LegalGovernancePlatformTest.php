<?php

use App\LegalGovernance\Actions\AssignLegalObligations;
use App\LegalGovernance\Actions\ImportLegalMarkdown;
use App\LegalGovernance\Actions\RecordLegalAcceptance;
use App\LegalGovernance\Actions\TransitionLegalVersion;
use App\LegalGovernance\Actions\WithdrawOptionalConsent;
use App\LegalGovernance\Enums\LegalVersionStatus;
use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Models\LegalWorkflowRequirement;
use App\LegalGovernance\Services\LegalContentRenderer;
use App\LegalGovernance\Services\LegalPlaceholderScanner;
use App\LegalGovernance\Support\CanonicalJson;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function legalVersion(array $overrides = []): LegalDocumentVersion
{
    $document = LegalDocument::create(['public_id' => (string) Str::uuid(), 'application_key' => 'test', 'slug' => 'terms', 'title' => 'Terms', 'document_type' => 'agreement', 'category' => 'user', 'visibility' => 'public', 'default_locale' => 'en', 'is_active' => true, 'requires_acceptance_default' => true]);

    return LegalDocumentVersion::create([...['public_id' => (string) Str::uuid(), 'legal_document_id' => $document->id, 'version_label' => '1.0', 'locale' => 'en', 'status' => 'draft', 'markdown_source' => '# Terms', 'sanitized_html' => '<h1>Terms</h1>', 'plain_text' => 'Terms', 'content_checksum' => hash('sha256', '# Terms'), 'metadata' => []], ...$overrides]);
}

test('legal markdown renderer strips executable html and produces stable checksums', function () {
    $renderer = app(LegalContentRenderer::class);
    $a = $renderer->render("# Safe\n<script>alert(1)</script>");
    $b = $renderer->render("# Safe\n<script>alert(1)</script>");
    expect($a['html'])->not->toContain('<script>')->and($a['checksum'])->toBe($b['checksum']);
});

test('placeholder scanner identifies release blockers', function () {
    $found = app(LegalPlaceholderScanner::class)->scan('Contact [LEGAL CONTACT EMAIL] and [TO BE CONFIRMED].');
    expect($found)->toHaveCount(2)->and($found[0]['release_blocking'])->toBeTrue();
});

test('manifest importer creates database drafts without publishing', function () {
    $result = app(ImportLegalMarkdown::class)->import();
    expect($result['created'])->toBeGreaterThan(20)->and(LegalDocumentVersion::query()->where('status', 'published')->count())->toBe(0);
});

test('published versions and acceptance evidence are immutable', function () {
    $version = legalVersion(['status' => 'published', 'published_at' => now(), 'effective_at' => now()]);
    expect(fn () => $version->update(['markdown_source' => 'changed']))->toThrow(LogicException::class);
    $acceptance = LegalAcceptance::withoutEvents(fn () => LegalAcceptance::create(['public_id' => (string) Str::uuid(), 'legal_document_version_id' => $version->id, 'actor_type' => 'user', 'actor_id' => '1', 'acceptance_type' => 'acknowledgement', 'status' => 'accepted', 'accepted_at' => now(), 'acceptance_method' => 'test', 'acceptance_statement' => 'ack', 'locale' => 'en', 'presented_checksum' => $version->content_checksum, 'manifest_checksum' => str_repeat('a', 64), 'evidence_checksum' => str_repeat('b', 64), 'evidence' => [], 'created_at' => now()]));
    expect(fn () => $acceptance->update(['status' => 'withdrawn']))->toThrow(LogicException::class);
});

test('reviewed checksum is required for configured approval', function () {
    $version = legalVersion(['status' => 'in_review']);
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL]);
    expect(fn () => app(TransitionLegalVersion::class)->approve($version, $counsel, ['legal']))->toThrow(LogicException::class);
    $version->reviews()->create(['review_type' => 'legal', 'reviewer_type' => 'user', 'reviewer_id' => '1', 'decision' => 'approved', 'reviewed_checksum' => $version->content_checksum, 'reviewed_at' => now()]);
    expect(app(TransitionLegalVersion::class)->approve($version, $counsel, ['legal'])->status)->toBe(LegalVersionStatus::Approved);
});

test('canonical json has deterministic key ordering', function () {
    expect(CanonicalJson::encode(['z' => 1, 'a' => ['y' => 2, 'b' => 3]]))->toBe('{"a":{"b":3,"y":2},"z":1}');
});

test('legal counsel area is permission scoped', function () {
    $ordinary = User::factory()->create(['role' => LineWattRole::SUBSCRIBER]);
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL]);
    $this->actingAs($ordinary)->get('/admin/legal-governance')->assertForbidden();
    $this->actingAs($counsel)->get('/admin/legal-governance')->assertOk();
});

test('public portal does not expose drafts', function () {
    legalVersion();
    $this->get('/legal')->assertOk()->assertDontSee('Terms');
});

test('workflow obligations are deduplicated and bind an exact version', function () {
    $version = legalVersion(['status' => 'published', 'published_at' => now(), 'effective_at' => now()]);
    $workflow = LegalWorkflow::create(['public_id' => (string) Str::uuid(), 'application_key' => 'test', 'slug' => 'registration', 'name' => 'Registration', 'trigger_type' => 'registration', 'audience' => 'users', 'status' => 'active', 'priority' => 1, 'blocking_behavior' => 'next_login_block']);
    LegalWorkflowRequirement::create(['legal_workflow_id' => $workflow->id, 'legal_document_id' => $version->legal_document_id, 'sequence' => 1, 'version_selection_rule' => 'current_effective', 'acceptance_type' => 'clickwrap_acceptance', 'is_required' => true, 'blocking_behavior' => 'next_login_block']);
    $identity = ['type' => 'user', 'id' => '42', 'organisation_type' => null, 'organisation_id' => null];
    app(AssignLegalObligations::class)->handle($workflow, $identity);
    app(AssignLegalObligations::class)->handle($workflow, $identity);
    expect(LegalObligation::query()->count())->toBe(1)->and(LegalObligation::query()->first()->legal_document_version_id)->toBe($version->id);
});

test('acceptance and later consent withdrawal remain separate append only records', function () {
    $version = legalVersion(['status' => 'published', 'published_at' => now(), 'effective_at' => now()]);
    $identity = ['type' => 'user', 'id' => '7', 'name' => 'Person', 'organisation_type' => null, 'organisation_id' => null];
    $acceptance = app(RecordLegalAcceptance::class)->handle($version, $identity, 'optional_consent', 'I opt in.');
    $withdrawal = app(WithdrawOptionalConsent::class)->handle($acceptance, '7');
    expect($acceptance->refresh()->status)->toBe('accepted')->and($withdrawal->status)->toBe('withdrawn')->and(LegalAcceptance::query()->count())->toBe(2);
});
