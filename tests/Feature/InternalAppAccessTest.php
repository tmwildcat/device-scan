<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\InternalApplication;
use App\Models\InternalApplicationAccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function internalAppPayload(array $overrides = []): array
{
    return [
        'name' => 'LineWatt Studio',
        'description' => 'First-party Studio integration.',
        'environment' => 'staging',
        'allowed_domains' => "https://studio.linewatt.test\nhttps://studio.swem2m.test",
        'scopes' => ['library.search', 'library.view_record'],
        ...$overrides,
    ];
}

function createInternalApplication(array $scopes = ['library.search']): array
{
    $secret = InternalApplication::generateSecret();
    $application = new InternalApplication([
        'name' => 'Internal Test App',
        'environment' => 'local',
        'status' => 'active',
        'allowed_domains' => ['https://studio.linewatt.test'],
        'scopes' => $scopes,
    ]);
    $application->setPlainSecret($secret);
    $application->save();

    return [$application, $secret];
}

it('allows super admin to create an internal application and reveals the secret once', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);

    $response = $this->actingAs($superAdmin)
        ->post('/admin/platform/internal-app-access', internalAppPayload());

    $application = InternalApplication::query()->firstOrFail();
    $response->assertRedirect(route('admin.platform.internal-app-access.show', $application));

    expect($application->client_id)->toStartWith('lwia_')
        ->and($application->secret_hash)->not->toBeEmpty()
        ->and($application->secret_hash)->not->toStartWith('lwias_')
        ->and($application->allowed_domains)->toBe(['https://studio.linewatt.test', 'https://studio.swem2m.test'])
        ->and($application->scopes)->toBe(['library.search', 'library.view_record']);

    $this->actingAs($superAdmin)
        ->get(route('admin.platform.internal-app-access.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/InternalAppAccessShow')
            ->whereNot('oneTimeSecret', null)
        );

    $this->actingAs($superAdmin)
        ->get(route('admin.platform.internal-app-access.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('oneTimeSecret', null)
        );
});

it('blocks non-super-admin users from internal app access pages', function () {
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get('/admin/platform/internal-app-access')
        ->assertForbidden();
});

it('denies revoked internal applications from the internal API', function () {
    [$application, $secret] = createInternalApplication(['library.search']);
    $application->update(['status' => 'revoked', 'revoked_at' => now()]);

    $this->getJson('/api/internal/library/search', [
        'X-LineWatt-Client-Id' => $application->client_id,
        'X-LineWatt-Client-Secret' => $secret,
    ])->assertForbidden();
});

it('denies internal API calls when the required scope is missing', function () {
    [$application, $secret] = createInternalApplication(['library.view_record']);

    $this->getJson('/api/internal/library/search', [
        'X-LineWatt-Client-Id' => $application->client_id,
        'X-LineWatt-Client-Secret' => $secret,
    ])->assertForbidden();
});

it('allows scoped internal applications to call health and search endpoints', function () {
    [$application, $secret] = createInternalApplication(['library.search']);

    DeviceDatasheet::query()->create([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'series' => 'Tiger Neo',
        'product_name' => 'Tiger Neo',
        'status' => 'published',
        'datasheet_disk' => 'local',
        'datasheet_path' => 'test/jinko.pdf',
        'datasheet_sha256' => str_repeat('a', 64),
    ])->compiledRecords()->create([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'series' => 'Tiger Neo',
        'model_series' => 'JKM',
        'display_name' => 'JKM 600W',
        'power_class_w' => 600,
        'status' => 'published',
        'compiled_disk' => 'local',
        'compiled_path' => 'test/jinko.json',
        'compiled_sha256' => str_repeat('b', 64),
    ]);

    $headers = [
        'X-LineWatt-Client-Id' => $application->client_id,
        'X-LineWatt-Client-Secret' => $secret,
    ];

    $this->getJson('/api/internal/health', $headers)
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    $this->getJson('/api/internal/library/search?q=jinko', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.manufacturer', 'Jinko Solar');

    expect(InternalApplicationAccessLog::query()->count())->toBe(2);
    expect($application->fresh()->last_used_at)->not->toBeNull();
});

it('regenerates an internal application secret and invalidates the old secret', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);
    [$application, $oldSecret] = createInternalApplication(['library.search']);

    $this->actingAs($superAdmin)
        ->post(route('admin.platform.internal-app-access.regenerate-secret', $application))
        ->assertRedirect(route('admin.platform.internal-app-access.show', $application));

    $application = $application->fresh();

    expect(Hash::check($oldSecret, $application->secret_hash))->toBeFalse();

    $this->actingAs($superAdmin)
        ->get(route('admin.platform.internal-app-access.show', $application))
        ->assertInertia(fn ($page) => $page->whereNot('oneTimeSecret', null));
});
