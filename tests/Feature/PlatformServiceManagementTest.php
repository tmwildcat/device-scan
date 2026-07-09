<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\PlatformService;
use App\Models\User;
use Database\Seeders\PlatformServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only super admin to access service management', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN, 'email_verified_at' => now()]);

    $this->seed(PlatformServiceSeeder::class);

    $this->actingAs($superAdmin)
        ->get('/admin/platform/services')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('LineWatt/PlatformServicesIndex'));

    $this->actingAs($admin)
        ->get('/admin/platform/services')
        ->assertForbidden();
});

it('seeds the initial platform services', function () {
    $this->seed(PlatformServiceSeeder::class);

    expect(PlatformService::query()->count())->toBe(8)
        ->and(PlatformService::query()->where('service_key', 'internal-library-api')->exists())->toBeTrue()
        ->and(PlatformService::query()->where('service_key', 'mcp-gateway')->whereJsonContains('required_scopes', 'mcp.tools')->exists())->toBeTrue()
        ->and(PlatformService::query()->where('service_key', 'module-compiler')->exists())->toBeTrue()
        ->and(PlatformService::query()->where('service_key', 'inverter-compiler')->exists())->toBeTrue();
});

it('opens a service detail page', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);
    $this->seed(PlatformServiceSeeder::class);

    $service = PlatformService::query()->where('service_key', 'mcp-gateway')->firstOrFail();

    $this->actingAs($superAdmin)
        ->get(route('admin.platform.services.show', $service))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/PlatformServiceShow')
            ->where('service.service_key', 'mcp-gateway')
            ->where('service.required_scopes.0', 'mcp.tools')
        );
});

it('updates service registry status without touching runtime routes', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);
    $this->seed(PlatformServiceSeeder::class);

    $service = PlatformService::query()->where('service_key', 'internal-library-api')->firstOrFail();

    $this->actingAs($superAdmin)
        ->post(route('admin.platform.services.pause', $service))
        ->assertRedirect(route('admin.platform.services.show', $service));

    expect($service->fresh()->status)->toBe('paused');

    $this->actingAs($superAdmin)
        ->post(route('admin.platform.services.pause', $service));

    expect($service->fresh()->status)->toBe('active');

    $this->actingAs($superAdmin)
        ->post(route('admin.platform.services.health-check', $service));

    expect($service->fresh()->last_health_check_at)->not->toBeNull()
        ->and($service->fresh()->last_status_message)->toContain('placeholder');
});
