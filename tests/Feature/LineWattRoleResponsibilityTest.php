<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects users to the correct default workspace', function (string $role, string $path) {
    $user = User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect($path);
})->with([
    'super admin' => [LineWattRole::SUPER_ADMIN, '/admin/platform'],
    'admin' => [LineWattRole::ADMIN, '/admin/business'],
    'librarian' => [LineWattRole::LIBRARIAN, '/admin/library'],
    'library publisher' => [LineWattRole::LIBRARY_PUBLISHER, '/publisher'],
    'manufacturer admin' => [LineWattRole::PARTNER_ADMIN, '/admin/manufacturer'],
    'manufacturer user' => [LineWattRole::PARTNER_USER, '/admin/manufacturer'],
    'library champion' => [LineWattRole::LIBRARY_CHAMPION, '/champion'],
    'subscriber' => [LineWattRole::SUBSCRIBER, '/my-library'],
    'registered user' => [LineWattRole::GUEST, '/'],
]);

it('keeps platform administration super-admin only', function () {
    $superAdmin = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN, 'email_verified_at' => now()]);
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN, 'email_verified_at' => now()]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN, 'email_verified_at' => now()]);

    $this->actingAs($superAdmin)->get('/admin/platform')->assertOk();
    $this->actingAs($admin)->get('/admin/platform')->assertForbidden();
    $this->actingAs($librarian)->get('/admin/platform')->assertForbidden();
});

it('allows admin but not librarian to access business growth operations', function () {
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN, 'email_verified_at' => now()]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN, 'email_verified_at' => now()]);

    $this->actingAs($admin)->get('/admin/business')->assertOk();
    $this->actingAs($admin)->get('/admin/business/discovery')->assertOk();
    $this->actingAs($admin)->get('/admin/business/compiler')->assertOk();

    $this->actingAs($librarian)->get('/admin/business')->assertForbidden();
    $this->actingAs($librarian)->get('/admin/business/discovery')->assertForbidden();
    $this->actingAs($librarian)->get('/admin/business/compiler')->assertForbidden();
});

it('keeps promotions and champions out of librarian access', function () {
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN, 'email_verified_at' => now()]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN, 'email_verified_at' => now()]);

    $this->actingAs($admin)->get('/admin/library/promotions')->assertOk();
    $this->actingAs($admin)->get('/admin/library/champions')->assertOk();

    $this->actingAs($librarian)->get('/admin/library/promotions')->assertForbidden();
    $this->actingAs($librarian)->get('/admin/library/champions')->assertForbidden();
});

it('keeps subscribers out of admin workspaces', function () {
    $subscriber = User::factory()->create(['role' => LineWattRole::SUBSCRIBER, 'email_verified_at' => now()]);

    $this->actingAs($subscriber)->get('/admin/platform')->assertForbidden();
    $this->actingAs($subscriber)->get('/admin/business')->assertForbidden();
    $this->actingAs($subscriber)->get('/admin/library')->assertForbidden();
    $this->actingAs($subscriber)->get('/admin/manufacturer')->assertForbidden();
});

it('prevents manufacturer users from managing users and subscription pages', function () {
    $manufacturerUser = User::factory()->create(['role' => LineWattRole::PARTNER_USER, 'email_verified_at' => now()]);
    $manufacturerAdmin = User::factory()->create(['role' => LineWattRole::PARTNER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($manufacturerUser)->get('/admin/manufacturer')->assertOk();
    $this->actingAs($manufacturerUser)->get('/admin/manufacturer/users')->assertForbidden();
    $this->actingAs($manufacturerUser)->get('/admin/manufacturer/upgrade')->assertForbidden();

    $this->actingAs($manufacturerAdmin)->get('/admin/manufacturer/users')->assertOk();
    $this->actingAs($manufacturerAdmin)->get('/admin/manufacturer/upgrade')->assertOk();
});

it('preserves backward-compatible redirects', function () {
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN, 'email_verified_at' => now()]);
    $manufacturerAdmin = User::factory()->create(['role' => LineWattRole::PARTNER_ADMIN, 'email_verified_at' => now()]);

    $this->actingAs($librarian)->get('/central-library')->assertRedirect('/admin/library');
    $this->actingAs($manufacturerAdmin)->get('/partner')->assertRedirect('/admin/manufacturer');
    $this->actingAs($manufacturerAdmin)->get('/admin/oem')->assertRedirect('/admin/manufacturer');
});
