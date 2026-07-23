<?php

use App\LegalGovernance\Models\LegalAuditEvent;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Database\Seeders\LegalGovernanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('resolves authenticated landing pages with explicit platform and counsel precedence', function () {
    $resolver = app(EntitlementChecker::class);
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN]);
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL]);
    $subscriber = User::factory()->create(['role' => LineWattRole::SUBSCRIBER]);
    $manufacturer = User::factory()->create(['role' => LineWattRole::PARTNER_ADMIN]);
    $registered = User::factory()->create(['role' => LineWattRole::GUEST]);

    expect($resolver->landingPath($superAdmin))->toBe('/admin/platform')
        ->and($resolver->landingPath($counsel))->toBe('/admin/legal-governance')
        ->and($resolver->landingPath($subscriber))->toBe('/my-library')
        ->and($resolver->landingPath($manufacturer))->toBe('/admin/manufacturer')
        ->and($resolver->landingPath($registered))->toBe('/');
});

it('redirects counsel and super admin from the shared dashboard resolver', function () {
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL, 'email_verified_at' => now()]);
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($counsel)->get(route('dashboard'))->assertRedirect(route('legal-governance.dashboard'));
    $this->actingAs($superAdmin)->get(route('dashboard'))->assertRedirect(route('admin.platform'));
});

it('uses role-aware destinations after standard login', function () {
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL, 'email_verified_at' => now()]);
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->post(route('login.store'), ['email' => $counsel->email, 'password' => 'password'])
        ->assertRedirect(route('legal-governance.dashboard', absolute: false));
    auth()->logout();
    $this->post(route('login.store'), ['email' => $superAdmin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.platform', absolute: false));
});

it('enforces legal dashboard authorization on the server', function () {
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL, 'email_verified_at' => now()]);
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);
    $ordinary = User::factory()->create(['role' => LineWattRole::GUEST, 'email_verified_at' => now()]);

    $this->get(route('legal-governance.dashboard'))->assertRedirect(route('login'));
    $this->actingAs($ordinary)->get(route('legal-governance.dashboard'))->assertForbidden();
    $this->actingAs($counsel)->get(route('legal-governance.dashboard'))->assertOk();
    $this->actingAs($superAdmin)->get(route('legal-governance.dashboard'))->assertOk();

    config(['legal-governance.permissions' => []]);
    $this->actingAs($counsel)->get(route('legal-governance.dashboard'))->assertForbidden();
});

it('renders scoped legal metrics and allow-listed recent activity', function () {
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL, 'email_verified_at' => now()]);
    $document = LegalDocument::query()->create([
        'public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'dashboard-test',
        'title' => 'Dashboard Test', 'document_type' => 'policy', 'category' => 'governance', 'visibility' => 'internal',
    ]);
    LegalDocumentVersion::query()->create([
        'public_id' => (string) Str::uuid(), 'legal_document_id' => $document->id, 'version_label' => '1.0',
        'status' => 'draft', 'markdown_source' => '# Test', 'sanitized_html' => '<h1>Test</h1>',
        'plain_text' => 'Test', 'content_checksum' => hash('sha256', 'Test'),
    ]);
    LegalAuditEvent::query()->create([
        'public_id' => (string) Str::uuid(), 'event_type' => 'document.created', 'occurred_at' => now(),
        'summary' => 'Document created', 'ip_address' => '192.0.2.10', 'user_agent' => 'sensitive-agent',
        'metadata' => ['secret' => 'not-for-dashboard'],
    ]);

    $response = $this->actingAs($counsel)->get(route('legal-governance.dashboard'));
    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('LineWatt/LegalGovernanceDashboard')
        ->where('dashboard.metrics.documents', 1)
        ->where('dashboard.metrics.drafts', 1)
        ->where('dashboard.recent_activity.0.summary', 'Document created')
        ->missing('dashboard.recent_activity.0.ip_address')
        ->missing('dashboard.recent_activity.0.metadata')
        ->where('workspace.navigation.0.route', 'legal-governance.dashboard')
    );
    $response->assertDontSee('192.0.2.10')->assertDontSee('sensitive-agent')->assertDontSee('not-for-dashboard');
});

it('renders linked legal documents inside the shared Inertia shell', function () {
    $counsel = User::factory()->create(['role' => LineWattRole::LEGAL_COUNSEL, 'email_verified_at' => now()]);
    $document = LegalDocument::query()->create([
        'public_id' => (string) Str::uuid(), 'application_key' => 'linewatt-library', 'slug' => 'linked-document',
        'title' => 'Linked Document', 'document_type' => 'policy', 'category' => 'governance', 'visibility' => 'internal',
    ]);
    $version = LegalDocumentVersion::query()->create([
        'public_id' => (string) Str::uuid(), 'legal_document_id' => $document->id, 'version_label' => '1.0',
        'status' => 'draft', 'markdown_source' => '# Linked', 'sanitized_html' => '<h1>Linked</h1>',
        'plain_text' => 'Linked', 'content_checksum' => hash('sha256', 'Linked'),
    ]);

    $this->actingAs($counsel)->get(route('legal-governance.documents'))->assertInertia(fn ($page) => $page
        ->component('LineWatt/LegalGovernanceDocuments')
        ->where('documents.data.0.title', 'Linked Document')
        ->where('documents.data.0.versions.0.href', route('legal-governance.versions.edit', $version, false))
    );
    $this->actingAs($counsel)->get(route('legal-governance.versions.edit', $version))->assertInertia(fn ($page) => $page
        ->component('LineWatt/LegalGovernanceVersionEdit')
        ->where('version.editable', true)
    );
});

it('exposes legal governance in the super admin workspace navigation metadata', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)->get(route('admin.platform'))->assertInertia(fn ($page) => $page
        ->where('workspace.nav_groups.1', 'Legal Governance')
        ->where('auth.user.workspaces.legal_governance', true)
    );
});

it('seeds an idempotent verified counsel demo user with the expected landing page', function () {
    $this->seed(LegalGovernanceSeeder::class);
    $this->seed(LegalGovernanceSeeder::class);

    $counsel = User::query()->where('email', 'legal-counsel@linewatt.test')->sole();
    $publisher = User::query()->where('email', 'legal-publisher@linewatt.test')->sole();

    expect($counsel->role)->toBe(LineWattRole::LEGAL_COUNSEL)
        ->and($counsel->email_verified_at)->not->toBeNull()
        ->and($counsel->hasLegalPermission('legal.dashboard.view'))->toBeTrue()
        ->and(app(EntitlementChecker::class)->landingPath($counsel))->toBe('/admin/legal-governance')
        ->and(User::query()->where('email', 'legal-counsel@linewatt.test')->count())->toBe(1);
    expect($publisher->role)->toBe(LineWattRole::LEGAL_PUBLISHER)
        ->and($publisher->hasLegalPermission('legal.versions.submit_review'))->toBeTrue()
        ->and($publisher->hasLegalPermission('legal.versions.approve'))->toBeFalse()
        ->and(User::query()->where('email', 'legal-publisher@linewatt.test')->count())->toBe(1);
});
