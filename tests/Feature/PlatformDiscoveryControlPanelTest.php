<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\SeoLandingPage;
use App\Models\SeoMetadata;
use App\Models\SeoRedirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows super admin to access the discovery dashboard', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    SeoLandingPage::query()->create([
        'kind' => 'technology',
        'title' => 'TOPCon Modules',
        'slug' => 'topcon-modules',
        'description' => 'TOPCon module discovery page.',
        'status' => 'published',
    ]);

    SeoMetadata::query()->create([
        'entity_type' => 'landing_page',
        'entity_kind' => 'technology',
        'locale' => 'en',
        'slug' => 'topcon-modules',
        'canonical_path' => '/technology/topcon-modules',
        'meta_title' => null,
        'meta_description' => null,
        'indexable' => true,
        'status' => 'published',
    ]);

    $this->actingAs($superAdmin)
        ->get('/admin/platform/discovery')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformDiscovery')
            ->where('section', 'dashboard')
            ->where('summary.public_pages', 2)
            ->where('summary.missing_meta_titles', 1)
        );
});

it('blocks non-super-admin users from discovery', function (string $role) {
    $user = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/admin/platform/discovery')
        ->assertForbidden();
})->with([
    LineWattRole::ADMIN,
    LineWattRole::LIBRARIAN,
    LineWattRole::SUBSCRIBER,
]);

it('renders discovery sidebar section links as page props', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)
        ->get('/admin/platform/discovery')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sections.0.label', 'Dashboard')
            ->where('sections.1.label', 'Landing Pages')
            ->where('sections.9.label', 'AI Discoverability')
        );
});

it('serves sitemap and robots discovery pages', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)
        ->get('/admin/platform/discovery/sitemaps')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformDiscovery')
            ->where('section', 'sitemaps')
            ->where('rows.0.primary', 'sitemap.xml')
        );

    $this->actingAs($superAdmin)
        ->get('/admin/platform/discovery/robots')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformDiscovery')
            ->where('section', 'robots')
            ->where('robots', fn (string $robots) => str_contains($robots, 'Disallow: /admin/') && str_contains($robots, 'Sitemap:'))
        );
});

it('does not crash metadata discovery with empty data', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)
        ->get('/admin/platform/discovery/metadata')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformDiscovery')
            ->where('section', 'metadata')
            ->where('rows', [])
        );
});

it('creates redirects from the platform discovery redirect manager', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)
        ->post('/admin/platform/discovery/redirects', [
            'source_path' => '/old-linewatt-page',
            'target_path' => '/technology/topcon',
            'status_code' => 301,
            'reason' => 'slug cleanup',
        ])
        ->assertRedirect();

    expect(SeoRedirect::query()->where('source_path', '/old-linewatt-page')->where('target_path', '/technology/topcon')->exists())->toBeTrue();
});
