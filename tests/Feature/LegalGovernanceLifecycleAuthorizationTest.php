<?php

use App\LegalGovernance\Actions\PublishLegalVersion;
use App\LegalGovernance\Actions\RecordLegalReview;
use App\LegalGovernance\Actions\TransitionLegalVersion;
use App\LegalGovernance\Actions\UpdateLegalDraft;
use App\LegalGovernance\Models\LegalAuditEvent;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Services\PublicLegalDocumentService;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function lifecycleVersion(): LegalDocumentVersion
{
    $document = LegalDocument::create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'lifecycle-'.Str::random(8), 'title' => 'Lifecycle Terms', 'document_type' => 'agreement', 'category' => 'user', 'visibility' => 'public', 'is_active' => true]);

    return LegalDocumentVersion::create(['public_id' => (string) Str::uuid(), 'legal_document_id' => $document->id, 'version_label' => '1.0', 'status' => 'draft', 'markdown_source' => '# Terms', 'sanitized_html' => '<h1>Terms</h1>', 'plain_text' => 'Terms', 'content_checksum' => hash('sha256', '# Terms'), 'effective_at' => now(), 'metadata' => ['required_review_types' => ['legal']]]);
}

it('gives the legal publisher authoring and submission rights but no decision or publication rights', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LEGAL_PUBLISHER]);
    $version = lifecycleVersion();

    expect($publisher->hasLegalPermission('legal.documents.edit'))->toBeTrue()
        ->and($publisher->hasLegalPermission('legal.versions.submit_review'))->toBeTrue()
        ->and($publisher->hasLegalPermission('legal.versions.approve'))->toBeFalse()
        ->and($publisher->hasLegalPermission('legal.versions.publish'))->toBeFalse();

    app(UpdateLegalDraft::class)->handle($version, '# Publisher draft', 'Authored.', $publisher);
    app(TransitionLegalVersion::class)->submitForReview($version->refresh(), $publisher);
    expect(fn () => app(RecordLegalReview::class)->handle($version->refresh(), 'legal', $publisher, 'approved'))->toThrow(HttpException::class);
    expect(fn () => app(TransitionLegalVersion::class)->approve($version->refresh(), $publisher))->toThrow(HttpException::class);
});

it('allows counsel self-approval while preserving separate audited transitions and immutable approval', function () {
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL]);
    $version = lifecycleVersion();
    $transition = app(TransitionLegalVersion::class);

    $transition->submitForReview($version, $counsel);
    app(RecordLegalReview::class)->handle($version->refresh(), 'legal', $counsel, 'approved', 'Legally approved.');
    $approved = $transition->approve($version->refresh(), $counsel, ['legal']);

    expect($approved->approved_by)->toBe((string) $counsel->id)
        ->and($approved->approved_at)->not->toBeNull()
        ->and($approved->approved_checksum)->toBe($approved->content_checksum)
        ->and(fn () => $approved->update(['markdown_source' => '# Silent edit']))->toThrow(LogicException::class)
        ->and(LegalAuditEvent::where('event_type', 'legal_version_submitted_for_review')->exists())->toBeTrue()
        ->and(LegalAuditEvent::where('event_type', 'legal_version_approved')->exists())->toBeTrue();
});

it('publishes only after distinct approval and removes a withdrawn version from public resolution', function () {
    Storage::fake('local');
    config(['legal-governance.storage_disk' => 'local']);
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL]);
    $version = lifecycleVersion();
    $transition = app(TransitionLegalVersion::class);
    $transition->submitForReview($version, $counsel);
    app(RecordLegalReview::class)->handle($version->refresh(), 'legal', $counsel, 'approved', 'Approved.');
    $approved = $transition->approve($version->refresh(), $counsel, ['legal']);
    $published = app(PublishLegalVersion::class)->handle($approved, $counsel);

    expect($published->status->value)->toBe('published')
        ->and($published->approved_at)->not->toBeNull()
        ->and($published->published_at)->not->toBeNull()
        ->and(LegalAuditEvent::where('event_type', 'legal_version_approved')->exists())->toBeTrue()
        ->and(LegalAuditEvent::where('event_type', 'legal_version_published')->exists())->toBeTrue();

    $transition->withdraw($published, $counsel, 'Superseded operationally.');
    expect(app(PublicLegalDocumentService::class)->publicDocument($published->document->slug))->toBeNull();
});

it('retains the super administrator legal override', function () {
    $super = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN]);
    foreach (config('legal-governance.permissions') as $permission) {
        expect($super->hasLegalPermission($permission))->toBeTrue();
    }
});
