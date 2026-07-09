<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\Notification;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('device-scan-test');
    config([
        'device-scan.storage_disk' => 'device-scan-test',
        'device-scan.base_path' => 'device-scan',
    ]);
});

it('allows library publisher workspace but blocks central management', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);

    $this->actingAs($publisher)
        ->get(route('publisher'))
        ->assertOk();

    $this->actingAs($publisher)
        ->get(route('admin.library'))
        ->assertForbidden();
});

it('publisher submits a reviewed record for librarian approval and notifies librarians', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $record = workflowRecord($publisher, 'publisher_review');

    $this->actingAs($publisher)
        ->post(route('publisher.review.submit', ['record' => $record->uuid]))
        ->assertRedirect();

    $record->refresh();
    expect($record->status)->toBe('submitted_for_approval')
        ->and($record->review_status)->toBe('submitted')
        ->and(ReviewComment::query()->where('compiled_device_record_id', $record->id)->where('action', 'submitted_for_approval')->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $librarian->id)->where('type', 'EngineeringRecordSubmittedForApproval')->exists())->toBeTrue();
});

it('librarian can request changes with comments and notify submitter', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $record = workflowRecord($publisher, 'submitted_for_approval');

    $this->actingAs($librarian)
        ->post(route('central-library.review.changes-requested', ['record' => $record->uuid]), [
            'comment' => 'Please verify the warranty table against page 2.',
        ])
        ->assertRedirect();

    $record->refresh();
    expect($record->status)->toBe('changes_requested')
        ->and($record->review_status)->toBe('changes_requested')
        ->and(ReviewComment::query()->where('compiled_device_record_id', $record->id)->where('action', 'changes_requested')->value('comment'))->toBe('Please verify the warranty table against page 2.')
        ->and(Notification::query()->where('user_id', $publisher->id)->where('type', 'EngineeringRecordChangesRequested')->exists())->toBeTrue();
});

it('public search hides unpublished central workflow records', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    workflowRecord($publisher, 'submitted_for_approval', 'Hidden Publisher Record');
    workflowRecord($publisher, 'published', 'Published Record');

    $this->get(route('engineering-search.results', ['q' => 'Record']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/EngineeringSearchResults')
            ->has('records.data', 1)
            ->where('records.data.0.display_name', 'Published Record')
        );
});

it('opens datasheet compilation review before model review', function () {
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    $record = workflowRecord($publisher, 'compiled', 'Datasheet Parent 600W');
    CompiledDeviceRecord::create([
        'device_datasheet_id' => $record->device_datasheet_id,
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Datasheet Parent 605W',
        'power_class_w' => 605,
        'status' => 'compiled',
        'review_status' => 'not_reviewed',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/datasheet-parent-605w.json',
        'compiled_sha256' => str_repeat('e', 64),
        'metadata' => ['upload_workspace' => 'publisher', 'uploaded_by' => $publisher->id],
    ]);

    $this->actingAs($librarian)
        ->get(route('admin.library.datasheets.review', ['datasheet' => $record->device_datasheet_id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/DatasheetReview')
            ->where('summary.compiled_records_created', 2)
            ->has('records', 2)
        );
});

it('manufacturer datasheet submit updates child records and notifies librarians', function () {
    $company = manufacturerCompany('Vikram Solar');
    $manufacturerAdmin = manufacturerUser($company);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $record = manufacturerWorkflowRecord($manufacturerAdmin, $company, 'review_required');

    $this->actingAs($manufacturerAdmin)
        ->post(route('admin.manufacturer.datasheets.review.submit', ['datasheet' => $record->device_datasheet_id]))
        ->assertRedirect();

    $record->refresh();
    $record->datasheet->refresh();

    expect($record->datasheet->status)->toBe('submitted_for_approval')
        ->and($record->datasheet->review_status)->toBe('submitted')
        ->and($record->status)->toBe('submitted_for_approval')
        ->and($record->review_status)->toBe('submitted')
        ->and(Notification::query()->where('user_id', $librarian->id)->where('type', 'EngineeringRecordSubmittedForApproval')->exists())->toBeTrue();
});

it('librarian approves datasheet and publishes related compiled records', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $record = workflowRecord($publisher, 'submitted_for_approval', 'Publish Datasheet 600W');
    $second = CompiledDeviceRecord::create([
        'device_datasheet_id' => $record->device_datasheet_id,
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Publish Datasheet 605W',
        'power_class_w' => 605,
        'status' => 'submitted_for_approval',
        'review_status' => 'submitted',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/publish-datasheet-605w.json',
        'compiled_sha256' => str_repeat('f', 64),
        'metadata' => ['upload_workspace' => 'publisher', 'uploaded_by' => $publisher->id, 'submitted_by' => $publisher->id],
    ]);

    $this->actingAs($librarian)
        ->post(route('admin.library.datasheets.review.approve', ['datasheet' => $record->device_datasheet_id]))
        ->assertRedirect();

    $record->refresh();
    $second->refresh();
    $record->datasheet->refresh();

    expect($record->datasheet->status)->toBe('published')
        ->and($record->status)->toBe('published')
        ->and($second->status)->toBe('published')
        ->and($record->review_status)->toBe('approved')
        ->and($second->review_status)->toBe('approved');
});

it('blocks LineWatt-hosted PDF download for internal only datasheets', function () {
    $viewer = User::factory()->create(['role' => LineWattRole::GUEST]);
    $record = pdfPolicyRecord([
        'pdf_access_mode' => 'internal_only',
        'can_public_download' => false,
        'can_public_preview' => false,
    ]);

    $this->actingAs($viewer)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'datasheet']))
        ->assertForbidden();
});

it('shows manufacturer source link for external link only records', function () {
    $record = pdfPolicyRecord([
        'pdf_access_mode' => 'external_link_only',
        'source_url' => 'https://example.com/datasheets/jinko.pdf',
        'source_domain' => 'example.com',
        'can_public_download' => false,
        'can_public_preview' => false,
    ]);

    $this->get(route('records.show', ['record' => $record->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/EngineeringRecordDetail')
            ->where('pdfPolicy.source_url', 'https://example.com/datasheets/jinko.pdf')
            ->where('pdfPolicy.pdf_label', 'External Link Only')
            ->where('exportOptions.0.enabled', false)
        );
});

it('allows partner supplied PDFs to be downloaded by the owning manufacturer user', function () {
    $partner = User::factory()->create(['role' => LineWattRole::PARTNER_ADMIN]);
    $record = pdfPolicyRecord([
        'source_type' => 'partner_submitted',
        'partner_id' => $partner->id,
        'pdf_access_mode' => 'partner_supplied',
        'permission_status' => 'partner_authorized',
        'can_public_download' => true,
        'can_public_preview' => true,
        'can_private_download' => true,
    ], [
        'source_type' => 'partner_submitted',
        'partner_id' => $partner->id,
        'status' => 'published',
    ]);

    $this->actingAs($partner)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'datasheet']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('does not expose tenant private PDFs through public record detail', function () {
    $owner = User::factory()->create(['role' => LineWattRole::SUBSCRIBER]);
    $record = pdfPolicyRecord([
        'source_type' => 'tenant_private',
        'tenant_id' => $owner->id,
        'pdf_access_mode' => 'user_private',
        'can_public_download' => false,
        'can_public_preview' => false,
    ], [
        'source_type' => 'tenant_private',
        'tenant_id' => $owner->id,
    ]);

    $this->get(route('records.show', ['record' => $record->uuid]))
        ->assertNotFound();
});

it('allows librarians to preview internal review PDFs', function () {
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $record = pdfPolicyRecord([
        'pdf_access_mode' => 'internal_only',
        'can_internal_preview' => true,
    ]);

    $this->actingAs($librarian)
        ->get(route('admin.library.datasheets.review.source-pdf', ['datasheet' => $record->device_datasheet_id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('allows subscriber derived data export even when original PDF hosting is restricted', function () {
    $subscriber = User::factory()->create(['role' => LineWattRole::SUBSCRIBER]);
    $record = pdfPolicyRecord([
        'pdf_access_mode' => 'internal_only',
        'can_public_download' => false,
        'can_public_preview' => false,
    ]);

    $this->actingAs($subscriber)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'csv']))
        ->assertOk()
        ->assertSee('manufacturer');
});

it('saves edited review fields into the reviewed payload and record metadata', function () {
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Old Solar',
        'product_name' => 'Old Module',
        'status' => 'compiled',
        'review_status' => 'not_reviewed',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'source.pdf',
        'datasheet_original_filename' => 'source.pdf',
        'datasheet_mime_type' => 'application/pdf',
        'datasheet_size_bytes' => 100,
        'datasheet_sha256' => str_repeat('a', 64),
    ]);
    Storage::disk('device-scan-test')->put('compiled/original.json', json_encode([
        'manufacturer' => 'Old Solar',
        'display_name' => 'Old Module 600W',
        'device_type' => 'module',
        'electrical_stc' => ['models' => []],
    ]));
    $record = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Old Solar',
        'display_name' => 'Old Module 600W',
        'status' => 'compiled',
        'review_status' => 'not_reviewed',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/original.json',
        'compiled_sha256' => str_repeat('b', 64),
    ]);

    $this->actingAs($librarian)
        ->patch(route('central-library.review.save', ['record' => $record->uuid]), [
            'sections' => [
                [
                    'key' => 'identity',
                    'title' => 'Identity',
                    'rows' => [
                        ['path' => 'identity.manufacturer', 'field' => 'Manufacturer', 'value' => 'New Solar'],
                        ['path' => 'identity.display_name', 'field' => 'Display Name', 'value' => 'New Module 600W'],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $record->refresh();
    $reviewArtifact = $record->metadata['review_artifact'];
    $reviewJson = json_decode(Storage::disk($reviewArtifact['disk'])->get($reviewArtifact['path']), true);
    $compiledJson = json_decode(Storage::disk('device-scan-test')->get('compiled/original.json'), true);

    expect($record->manufacturer)->toBe('New Solar')
        ->and($record->display_name)->toBe('New Module 600W')
        ->and($reviewJson['reviewed_payload']['manufacturer'])->toBe('New Solar')
        ->and($reviewJson['reviewed_payload']['display_name'])->toBe('New Module 600W')
        ->and($compiledJson['manufacturer'])->toBe('Old Solar');
});

it('saving manufacturer review does not notify librarians', function () {
    $company = manufacturerCompany('Vikram Solar');
    $user = manufacturerUser($company);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $record = manufacturerWorkflowRecord($user, $company, 'publisher_review');

    $this->actingAs($user)
        ->patch(route('admin.manufacturer.engineering-data.review.save', ['record' => $record->uuid]), [
            'sections' => [[
                'key' => 'identity',
                'title' => 'Identity',
                'rows' => [[
                    'path' => 'identity.display_name',
                    'field' => 'Display Name',
                    'value' => 'Vikram Corrected',
                ]],
            ]],
        ])
        ->assertRedirect();

    expect(Notification::query()->where('user_id', $librarian->id)->count())->toBe(0)
        ->and($record->fresh()->status)->toBe('publisher_review')
        ->and($record->fresh()->review_status)->toBe('publisher_reviewed');
});

it('manufacturer submit for approval notifies librarian admin and super admin', function () {
    $company = manufacturerCompany('Vikram Solar');
    $user = manufacturerUser($company);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $admin = User::factory()->create(['role' => LineWattRole::ADMIN]);
    $super = User::factory()->create(['role' => LineWattRole::SUPER_ADMIN]);
    $record = manufacturerWorkflowRecord($user, $company, 'publisher_review');

    $this->actingAs($user)
        ->post(route('admin.manufacturer.engineering-data.review.submit', ['record' => $record->uuid]))
        ->assertRedirect();

    $record->refresh();
    expect($record->status)->toBe('submitted_for_approval')
        ->and($record->review_status)->toBe('submitted')
        ->and(Notification::query()->where('user_id', $librarian->id)->where('type', 'EngineeringRecordSubmittedForApproval')->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $admin->id)->where('type', 'EngineeringRecordSubmittedForApproval')->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $super->id)->where('type', 'EngineeringRecordSubmittedForApproval')->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $user->id)->where('type', 'EngineeringRecordSubmittedForApproval')->exists())->toBeFalse();
});

it('pending approval list includes manufacturer submissions', function () {
    $company = manufacturerCompany('Vikram Solar');
    $user = manufacturerUser($company);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    manufacturerWorkflowRecord($user, $company, 'submitted_for_approval', 'Vikram Submitted Record');

    $this->actingAs($librarian)
        ->get(route('admin.library.approval-queue', ['view' => 'pending_approval']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/WorkflowRecordList')
            ->has('records.data', 1)
            ->where('records.data.0.display_name', 'Vikram Submitted Record')
        );
});

it('pending approval dashboard count equals approval queue count', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    $company = manufacturerCompany('Vikram Solar');
    $manufacturer = manufacturerUser($company);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);

    workflowRecord($publisher, 'submitted_for_approval', 'Publisher Submitted Record');
    manufacturerWorkflowRecord($manufacturer, $company, 'submitted_for_approval', 'OEM Submitted Record');
    workflowRecord($publisher, 'published', 'Already Published Record');

    $this->actingAs($librarian)
        ->get(route('admin.library'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/CentralLibrary')
            ->where('summary.pending_review', 2)
        );

    $this->actingAs($librarian)
        ->get(route('admin.library.approval-queue', ['view' => 'pending_approval']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/WorkflowRecordList')
            ->has('records.data', 2)
        );
});

it('pending review engineering data does not include pending approval records', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);

    workflowRecord($publisher, 'submitted_for_approval', 'Waiting For Librarian Record');
    workflowRecord($publisher, 'compiled', 'Publisher Needs Review Record');

    $this->actingAs($librarian)
        ->get(route('admin.library.engineering-data', ['view' => 'pending_review']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/WorkflowRecordList')
            ->has('records.data', 1)
            ->where('records.data.0.display_name', 'Publisher Needs Review Record')
        );
});

it('approval queue includes publisher submissions', function () {
    $publisher = User::factory()->create(['role' => LineWattRole::LIBRARY_PUBLISHER]);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    workflowRecord($publisher, 'submitted_for_approval', 'Publisher Queue Record');

    $this->actingAs($librarian)
        ->get(route('admin.library.approval-queue', ['view' => 'pending_approval']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/WorkflowRecordList')
            ->has('records.data', 1)
            ->where('records.data.0.display_name', 'Publisher Queue Record')
            ->where('records.data.0.source_label', 'Publisher')
        );
});

it('library admin sidebar has a single approval queue link', function () {
    $sidebar = file_get_contents(resource_path('js/components/linewatt/admin/LibraryAdminShell.vue'));

    expect(substr_count($sidebar, "label: 'Approval Queue'"))->toBe(1)
        ->and($sidebar)->toContain("href: '/admin/library/approval-queue?view=pending_approval'")
        ->and($sidebar)->not->toContain("label: 'Pending Approval'")
        ->and($sidebar)->not->toContain("href: '/admin/library/pending-approval'");
});

it('librarian approve publishes manufacturer submission', function () {
    $company = manufacturerCompany('Vikram Solar');
    $user = manufacturerUser($company);
    $librarian = User::factory()->create(['role' => LineWattRole::LIBRARIAN]);
    $record = manufacturerWorkflowRecord($user, $company, 'submitted_for_approval', 'Vikram Approved Record');

    $this->actingAs($librarian)
        ->post(route('central-library.review.approve', ['record' => $record->uuid]))
        ->assertRedirect();

    $record->refresh();
    expect($record->source_type)->toBe('central_curated')
        ->and($record->status)->toBe('published')
        ->and($record->review_status)->toBe('approved');
});

function workflowRecord(User $submitter, string $status, string $displayName = 'Publisher Module 600W'): CompiledDeviceRecord
{
    $slug = str($displayName)->slug()->toString();

    $datasheet = DeviceDatasheet::create([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Publisher Module',
        'status' => $status,
        'review_status' => 'not_reviewed',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/'.$slug.'.pdf',
        'datasheet_sha256' => str_repeat('a', 64),
        'metadata' => ['upload_workspace' => 'publisher', 'uploaded_by' => $submitter->id],
    ]);

    return CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => $displayName,
        'power_class_w' => 600,
        'status' => $status,
        'review_status' => 'not_reviewed',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/'.$slug.'.json',
        'compiled_sha256' => str_repeat('b', 64),
        'metadata' => ['upload_workspace' => 'publisher', 'uploaded_by' => $submitter->id],
    ]);
}

function manufacturerCompany(string $name): ManufacturerCompany
{
    return ManufacturerCompany::create([
        'uuid' => (string) Str::uuid(),
        'name' => $name,
        'slug' => str($name)->slug()->toString(),
        'plan_code' => 'pro',
        'subscription_status' => 'contract_active',
        'max_users' => 3,
        'metadata' => ['manufacturer_aliases' => [$name]],
    ]);
}

function manufacturerUser(ManufacturerCompany $company): User
{
    return User::factory()->create([
        'role' => LineWattRole::PARTNER_ADMIN,
        'manufacturer_company_id' => $company->id,
        'manufacturer_role' => 'manufacturer_admin',
    ]);
}

function manufacturerWorkflowRecord(User $submitter, ManufacturerCompany $company, string $status, string $displayName = 'Vikram Module 600W'): CompiledDeviceRecord
{
    $slug = str($displayName)->slug()->toString();
    $reviewStatus = $status === 'submitted_for_approval' ? 'submitted' : 'not_reviewed';

    $datasheet = DeviceDatasheet::create([
        'source_type' => 'partner_submitted',
        'partner_id' => $submitter->id,
        'device_type' => 'module',
        'manufacturer' => $company->name,
        'product_name' => 'Vikram Module',
        'status' => $status,
        'review_status' => $reviewStatus,
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/'.$slug.'.pdf',
        'datasheet_sha256' => str_repeat('c', 64),
        'metadata' => [
            'upload_workspace' => 'partner',
            'uploaded_by' => $submitter->id,
            'manufacturer_company_id' => $company->id,
        ],
    ]);

    return CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'partner_submitted',
        'partner_id' => $submitter->id,
        'device_type' => 'module',
        'manufacturer' => $company->name,
        'display_name' => $displayName,
        'power_class_w' => 600,
        'status' => $status,
        'review_status' => $reviewStatus,
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/'.$slug.'.json',
        'compiled_sha256' => str_repeat('d', 64),
        'metadata' => [
            'upload_workspace' => 'partner',
            'uploaded_by' => $submitter->id,
            'submitted_by' => $status === 'submitted_for_approval' ? $submitter->id : null,
            'submitted_at' => $status === 'submitted_for_approval' ? now()->toIso8601String() : null,
        ],
    ]);
}

function pdfPolicyRecord(array $datasheetOverrides = [], array $recordOverrides = []): CompiledDeviceRecord
{
    $uuid = (string) Str::uuid();
    $datasheetPath = 'uploads/policy-'.$uuid.'.pdf';
    $compiledPath = 'compiled/policy-'.$uuid.'.json';
    $pdfContent = "%PDF-1.4\n% LineWatt test PDF\n";

    Storage::disk('device-scan-test')->put($datasheetPath, $pdfContent);
    Storage::disk('device-scan-test')->put($compiledPath, json_encode([
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Policy Test 600W',
        'device_type' => 'module',
        'electrical_stc' => ['models' => []],
    ]));

    $datasheet = DeviceDatasheet::create(array_merge([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Policy Test Datasheet',
        'status' => 'published',
        'review_status' => 'approved',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => $datasheetPath,
        'datasheet_original_filename' => 'policy-test.pdf',
        'datasheet_mime_type' => 'application/pdf',
        'datasheet_size_bytes' => strlen($pdfContent),
        'datasheet_sha256' => hash('sha256', $pdfContent),
        'pdf_access_mode' => 'internal_only',
        'permission_status' => 'unknown',
        'can_public_download' => false,
        'can_public_preview' => false,
        'can_internal_preview' => true,
        'can_private_download' => true,
    ], $datasheetOverrides));

    return CompiledDeviceRecord::create(array_merge([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => $datasheet->source_type,
        'tenant_id' => $datasheet->tenant_id,
        'partner_id' => $datasheet->partner_id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Policy Test 600W',
        'power_class_w' => 600,
        'status' => 'published',
        'review_status' => 'approved',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => $compiledPath,
        'compiled_sha256' => hash('sha256', Storage::disk('device-scan-test')->get($compiledPath)),
    ], $recordOverrides));
}
