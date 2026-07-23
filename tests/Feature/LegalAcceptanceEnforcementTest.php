<?php

use App\LegalGovernance\Actions\RecordLegalAcceptance;
use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Models\LegalWorkflowRequirement;
use App\LegalGovernance\Services\LegalAccessService;
use App\LegalGovernance\Services\PublicLegalDocumentService;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function enforcementFixture(string $capability = 'test.protected'): array
{
    $document = LegalDocument::create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'enforced-terms', 'title' => 'Enforced Terms', 'document_type' => 'agreement', 'category' => 'user', 'visibility' => 'public', 'is_active' => true]);
    $version = LegalDocumentVersion::create(['public_id' => (string) Str::uuid(), 'legal_document_id' => $document->id, 'version_label' => '1.0', 'status' => 'published', 'markdown_source' => '# Terms', 'sanitized_html' => '<h1>Terms</h1>', 'plain_text' => 'Terms', 'content_checksum' => hash('sha256', '# Terms'), 'published_at' => now(), 'effective_at' => now()]);
    $workflow = LegalWorkflow::create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'test-access', 'name' => 'Test access', 'trigger_type' => 'registration', 'audience' => 'registered_users', 'status' => 'active', 'priority' => 1, 'blocking_behavior' => 'next_login_block']);
    LegalWorkflowRequirement::create(['legal_workflow_id' => $workflow->id, 'legal_document_id' => $document->id, 'sequence' => 1, 'version_selection_rule' => 'current_published', 'acceptance_type' => 'clickwrap_acceptance', 'is_required' => true, 'blocking_behavior' => 'next_login_block', 'configuration' => ['statement' => 'I agree to the Enforced Terms.']]);
    config(['legal-governance.capabilities' => [...config('legal-governance.capabilities', []), $capability => ['workflow' => 'test-access', 'audience' => 'registered_users', 'blocking' => true]]]);

    return [$document, $version, $workflow];
}

it('fails closed for missing protected workflow configuration', function () {
    $user = User::factory()->create(['role' => LineWattRole::GUEST, 'email_verified_at' => now()]);
    $decision = app(LegalAccessService::class)->decisionForCapability($user, 'missing.capability');

    expect($decision->allowed)->toBeFalse()->and($decision->configurationValid)->toBeFalse()->and($decision->reasonCode)->toBe('workflow_not_found');
});

it('creates one blocking obligation and allows access after exact acceptance', function () {
    [, $version, $workflow] = enforcementFixture();
    $user = User::factory()->create(['role' => LineWattRole::GUEST, 'email_verified_at' => now()]);
    $service = app(LegalAccessService::class);

    expect($service->decisionForCapability($user, 'test.protected')->allowed)->toBeFalse();
    $service->decisionForCapability($user, 'test.protected');
    expect(LegalObligation::count())->toBe(1);
    $obligation = LegalObligation::firstOrFail();
    app(RecordLegalAcceptance::class)->handle($version, ['type' => User::class, 'id' => (string) $user->id], 'clickwrap_acceptance', 'I agree to the Enforced Terms.', ['subject_type' => 'user', 'subject_id' => (string) $user->id], $workflow, $obligation);

    expect($service->decisionForCapability($user, 'test.protected')->allowed)->toBeTrue()->and(LegalAcceptance::count())->toBe(1);
    expect(fn () => LegalAcceptance::firstOrFail()->update(['status' => 'withdrawn']))->toThrow(LogicException::class);
});

it('redirects browser requests and returns structured JSON without leaking content', function () {
    enforcementFixture();
    $user = User::factory()->create(['role' => LineWattRole::GUEST, 'email_verified_at' => now()]);
    Route::middleware(['web', 'auth', 'legal.acceptance:test.protected'])->get('/_test/legal-protected', fn () => 'allowed')->name('test.legal-protected');

    $this->actingAs($user)->get('/_test/legal-protected?tab=private')->assertRedirect(route('legal.acceptance.index'));
    expect(session('legal.intended.'.$user->id))->toBe('/_test/legal-protected?tab=private');
    $this->actingAs($user)->getJson('/_test/legal-protected')->assertForbidden()->assertJsonPath('code', 'legal_acceptance_required')->assertJsonMissing(['document']);
});

it('requires explicit affirmation and restores only a user-scoped internal destination', function () {
    enforcementFixture();
    $user = User::factory()->create(['role' => LineWattRole::GUEST, 'email_verified_at' => now()]);
    app(LegalAccessService::class)->decisionForCapability($user, 'test.protected');
    $obligation = LegalObligation::firstOrFail();

    $this->actingAs($user)->post(route('legal.acceptance.store', $obligation), ['affirmed' => false])->assertSessionHasErrors('affirmed');
    expect(LegalAcceptance::count())->toBe(0);
    $this->withSession(['legal.capability.'.$user->id => 'test.protected', 'legal.intended.'.$user->id => '//example.test/steal'])
        ->actingAs($user)->post(route('legal.acceptance.store', $obligation), ['affirmed' => true])->assertRedirect('/');
    expect(LegalAcceptance::count())->toBe(1)->and($obligation->refresh()->status)->toBe('completed');
});

it('shows only currently published effective documents in the public resolver and footer', function () {
    [$document, $version] = enforcementFixture();
    config(['legal-governance.public_footer_documents' => [['slug' => $document->slug, 'required' => true], ['slug' => 'draft-only', 'required' => false]]]);
    LegalDocument::create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'draft-only', 'title' => 'Draft Only', 'document_type' => 'policy', 'category' => 'user', 'visibility' => 'public', 'is_active' => true]);

    $service = app(PublicLegalDocumentService::class);
    expect($service->footerDocuments()->pluck('title')->all())->toBe(['Enforced Terms']);
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('publicLegalDocuments.0.title', 'Enforced Terms'));
    $this->get(route('legal.show', $document->slug))->assertOk()->assertSee('Viewing it does not record acceptance');
    expect(LegalAcceptance::count())->toBe(0);
    $this->get(route('legal.show', 'draft-only'))->assertNotFound();

    DB::table('legal_document_versions')->where('id', $version->id)->update(['effective_at' => now()->addDay()]);
    expect($service->publicDocument($document->slug))->toBeNull();
    DB::table('legal_document_versions')->where('id', $version->id)->update(['effective_at' => now(), 'status' => 'withdrawn']);
    expect($service->publicDocument($document->slug))->toBeNull();
});

it('keeps the public footer in normal layout flow and hides empty legal navigation', function () {
    $layout = file_get_contents(resource_path('js/components/linewatt/PublicSiteLayout.vue'));
    $navigation = file_get_contents(resource_path('js/components/linewatt/WorkspaceNavigation.vue'));

    expect($layout)
        ->not->toContain('Teleport')
        ->and($navigation)->not->toContain('PublicSiteFooter')
        ->and(strpos($layout, '<WorkspaceNavigation'))
        ->toBeLessThan(strpos($layout, '<main'))
        ->and(strpos($layout, '<main'))
        ->toBeLessThan(strpos($layout, '<PublicSiteFooter'));

    expect(view('legal._footer', ['footerDocuments' => collect()])->render())
        ->not->toContain('aria-label="Legal"');
});

it('reports exact required footer publication failures in production validation', function () {
    config(['legal-governance.public_footer_documents' => [['slug' => 'draft-only', 'required' => true]]]);
    LegalDocument::create(['public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'draft-only', 'title' => 'Draft Only', 'document_type' => 'policy', 'category' => 'user', 'visibility' => 'public', 'is_active' => true])
        ->versions()->create(['public_id' => (string) Str::uuid(), 'version_label' => '1.0', 'status' => 'draft', 'markdown_source' => '# Draft', 'sanitized_html' => '<h1>Draft</h1>', 'plain_text' => 'Draft', 'content_checksum' => hash('sha256', '# Draft')]);

    $this->artisan('legal:validate-documents', ['--production' => true])
        ->expectsOutputToContain('Public footer excludes draft-only: no published version exists (latest status: draft).')
        ->assertFailed();
});
