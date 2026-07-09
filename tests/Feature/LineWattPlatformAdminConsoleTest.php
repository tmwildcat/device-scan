<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only super admin to access platform administration', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN, 'email_verified_at' => now()]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN, 'email_verified_at' => now()]);
    $subscriber = User::factory()->create(['role' => LineWattRole::SUBSCRIBER, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)->get('/admin/platform')->assertOk();
    $this->actingAs($admin)->get('/admin/platform')->assertForbidden();
    $this->actingAs($librarian)->get('/admin/platform')->assertForbidden();
    $this->actingAs($subscriber)->get('/admin/platform')->assertForbidden();
});

it('exposes the expected platform sidebar groups to the page shell', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)
        ->get('/admin/platform')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformAdmin')
            ->where('workspace.nav_groups.0', 'Platform')
            ->where('workspace.nav_groups.1', 'Users & Security')
            ->where('workspace.nav_groups.2', 'Infrastructure')
        );
});

it('serves platform shell pages for operational sections', function (string $path, string $title) {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)
        ->get($path)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformAdmin')
            ->where('section.title', $title)
        );
})->with([
    ['/admin/platform/system-health', 'System Health'],
    ['/admin/platform/security', 'Security'],
    ['/admin/platform/storage', 'Storage'],
    ['/admin/platform/background-jobs', 'Background Jobs'],
    ['/admin/platform/queue-monitor', 'Queue Monitor'],
    ['/admin/platform/notifications', 'Notifications'],
    ['/admin/platform/logs', 'Logs'],
    ['/admin/platform/backup-recovery', 'Backup & Recovery'],
    ['/admin/platform/environment', 'Environment'],
    ['/admin/platform/feature-flags', 'Feature Flags'],
    ['/admin/platform/api-keys', 'Internal App Access'],
    ['/admin/platform/audit-logs', 'Audit Logs'],
    ['/admin/platform/developer-tools', 'Developer Tools'],
    ['/admin/platform/system-administrators', 'System Administrators'],
    ['/admin/platform/roles', 'Roles'],
    ['/admin/platform/permissions', 'Permissions'],
    ['/admin/platform/entitlements', 'Entitlements'],
    ['/admin/platform/authentication', 'Authentication'],
    ['/admin/platform/sso', 'Sso'],
    ['/admin/platform/object-storage', 'Object Storage'],
    ['/admin/platform/compiler-services', 'Compiler Services'],
    ['/admin/platform/search-index', 'Search Index'],
    ['/admin/platform/email', 'Email'],
    ['/admin/platform/scheduled-jobs', 'Scheduled Jobs'],
    ['/admin/platform/monitoring', 'Monitoring'],
]);

it('serves the dedicated internal app access page', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)
        ->get('/admin/platform/internal-app-access')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/InternalAppAccessIndex')
        );
});

it('does not expose secret values on the environment page', function () {
    config([
        'services.linewatt.secret' => 'super-secret-value',
        'linewatt-storage.secret_access_key' => 'never-show-this',
    ]);

    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $response = $this->actingAs($superAdmin)
        ->get('/admin/platform/environment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformAdmin')
            ->where('section.title', 'Environment')
        );

    $response->assertDontSee('super-secret-value');
    $response->assertDontSee('never-show-this');
    $response->assertDontSee('SECRET_ACCESS_KEY');
});
