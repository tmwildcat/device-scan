<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\ManufacturerCompany;
use App\Models\Notification;
use App\Models\PartnerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts public partner requests and notifies librarians', function () {
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);

    $this->post(route('partner.apply.store'), [
        'company_name' => 'Example Solar GmbH',
        'website' => 'https://example-solar.test',
        'country' => 'Germany',
        'contact_person' => 'Ada Engineer',
        'contact_email' => 'ada@example-solar.test',
        'official_email_domain' => 'example-solar.test',
        'requested_manufacturer_brand' => 'Example Solar',
        'proof_notes' => 'Official manufacturer representative.',
    ])->assertRedirect();

    expect(PartnerRequest::query()->where('company_name', 'Example Solar GmbH')->where('status', 'pending')->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $librarian->id)->where('type', 'PartnerRequestCreated')->exists())->toBeTrue();
});

it('approves a partner request into a manufacturer company without creating a manufacturer admin automatically', function () {
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN]);
    $request = PartnerRequest::create([
        'status' => 'pending',
        'company_name' => 'Example Solar GmbH',
        'country' => 'Germany',
        'contact_person' => 'Ada Engineer',
        'contact_email' => 'ada@example-solar.test',
        'official_email_domain' => 'example-solar.test',
        'requested_manufacturer_brand' => 'Example Solar',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.library.partner-requests.approve', ['partnerRequest' => $request]), [
            'plan_code' => 'pro',
            'comment' => 'Verified domain and brand ownership.',
        ])
        ->assertRedirect();

    $request->refresh();
    expect($request->status)->toBe('approved')
        ->and($request->manufacturer_company_id)->not->toBeNull()
        ->and(ManufacturerCompany::query()->where('name', 'Example Solar')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'ada@example-solar.test')->exists())->toBeFalse();
});

it('lets library admin suspend and reactivate a member', function () {
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN]);
    $member = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.library.members.suspend', ['member' => $member]))
        ->assertRedirect();

    expect($member->refresh()->subscription_status)->toBe('suspended');

    $this->actingAs($admin)
        ->post(route('admin.library.members.reactivate', ['member' => $member]))
        ->assertRedirect();

    expect($member->refresh()->subscription_status)->toBe('active');
});
