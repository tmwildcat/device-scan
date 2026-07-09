<?php

use App\DeviceScan\Compilers\Inverters\InverterCompiler;
use App\DeviceScan\Compilers\Modules\ModuleCompiler;
use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\Jobs\CompileDeviceDatasheetJob;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Manufacturers\ManufacturerNormalizer;
use App\LineWatt\Uploads\MalwareScanner;
use App\LineWatt\Uploads\NullMalwareScanner;
use App\LineWatt\Uploads\UploadSecurityService;
use App\Models\DeviceDatasheet;
use App\Models\CompiledDeviceRecord;
use App\Models\ManufacturerCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('device-scan-test');
    config([
        'device-scan.storage_disk' => 'device-scan-test',
        'device-scan.base_path' => 'device-scan',
        'linewatt-library.upload.max_pdf_size_mb' => 25,
        'linewatt-library.upload.malware_scan.enabled' => false,
        'linewatt-library.upload.malware_scan.fail_closed' => false,
    ]);
});

it('rejects non pdf uploads before permanent storage', function () {
    $service = app(UploadSecurityService::class);
    $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello');
    $path = $file->getPathname();

    $result = $service->inspect($path, $file, [
        'source_type' => 'tenant_private',
        'device_type' => 'module',
        'tenant_id' => 1,
    ]);

    expect($result->passed)->toBeFalse()
        ->and($result->errors)->toContain('unsupported_file_extension')
        ->and($result->errors)->toContain('invalid_pdf_signature');
});

it('rejects oversized pdf uploads', function () {
    config(['linewatt-library.upload.max_pdf_size_mb' => 1]);
    $service = app(UploadSecurityService::class);
    $file = UploadedFile::fake()->createWithContent('large.pdf', validPdfBytes(str_repeat('x', 1024 * 1024 + 50)));

    $result = $service->inspect($file->getPathname(), $file, [
        'source_type' => 'tenant_private',
        'device_type' => 'module',
        'tenant_id' => 1,
    ]);

    expect($result->passed)->toBeFalse()
        ->and($result->errors)->toContain('file_too_large');
});

it('accepts valid pdf uploads with a local malware scan warning', function () {
    $service = app(UploadSecurityService::class);
    $file = UploadedFile::fake()->createWithContent('module.pdf', validPdfBytes());

    $result = $service->inspect($file->getPathname(), $file, [
        'source_type' => 'tenant_private',
        'device_type' => 'module',
        'tenant_id' => 1,
    ]);

    expect($result->passed)->toBeTrue()
        ->and($result->sha256)->not->toBeNull()
        ->and($result->warnings)->toContain('malware_scan_skipped');
});

it('detects duplicate upload sha within the same source scope', function () {
    $file = UploadedFile::fake()->createWithContent('module.pdf', validPdfBytes());
    $sha = hash_file('sha256', $file->getPathname());
    DeviceDatasheet::create([
        'source_type' => 'tenant_private',
        'tenant_id' => 7,
        'device_type' => 'module',
        'status' => 'compiled',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'existing.pdf',
        'datasheet_sha256' => $sha,
    ]);

    $result = app(UploadSecurityService::class)->inspect($file->getPathname(), $file, [
        'source_type' => 'tenant_private',
        'device_type' => 'module',
        'tenant_id' => 7,
    ]);

    expect($result->passed)->toBeFalse()
        ->and($result->errors)->toContain('duplicate_upload');
});

it('blocks uploads when malware scanning fails closed', function () {
    app()->bind(MalwareScanner::class, fn () => new NullMalwareScanner());
    config([
        'linewatt-library.upload.malware_scan.enabled' => true,
        'linewatt-library.upload.malware_scan.fail_closed' => true,
    ]);
    $file = UploadedFile::fake()->createWithContent('module.pdf', validPdfBytes());

    $result = app(UploadSecurityService::class)->inspect($file->getPathname(), $file, [
        'source_type' => 'tenant_private',
        'device_type' => 'module',
        'tenant_id' => 1,
    ]);

    expect($result->passed)->toBeFalse()
        ->and($result->errors)->toContain('malware_scan_failed');
});

it('stores subscriber uploads as tenant private and sends user to compiled records result page', function () {
    Bus::fake();
    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);
    $file = UploadedFile::fake()->createWithContent('jinko.pdf', validPdfBytes());

    $this->actingAs($user)
        ->post(route('my-library.uploads.store'), [
            'device_type' => 'module',
            'manufacturer' => 'Jinko Solar',
            'product_name' => 'Tiger Neo',
            'datasheet' => $file,
        ])
        ->assertRedirect(route('device-scan.uploads.compiled-records', ['datasheet' => 1]));

    $datasheet = DeviceDatasheet::query()->firstOrFail();
    expect($datasheet->source_type)->toBe('tenant_private')
        ->and($datasheet->tenant_id)->toBe($user->id)
        ->and($datasheet->status)->toBe('security_checked')
        ->and($datasheet->datasheet_path)->toStartWith('device-scan/tenants/tenant-'.$user->id.'/datasheets/module/');

    expect($datasheet->compiledRecords()->count())->toBe(0);
});

it('keeps partner submissions in partner submitted scope and sends user to compiled records result page', function () {
    Bus::fake();
    $company = ManufacturerCompany::create([
        'uuid' => (string) Str::uuid(),
        'name' => 'Sungrow',
        'slug' => 'sungrow',
        'plan_code' => 'pro',
        'subscription_status' => 'contract_active',
        'max_users' => 3,
        'metadata' => ['manufacturer_aliases' => ['Sungrow']],
    ]);
    $user = User::factory()->create([
        'role' => LineWattRole::PARTNER_USER,
        'manufacturer_company_id' => $company->id,
        'manufacturer_role' => 'manufacturer_user',
    ]);
    $file = UploadedFile::fake()->createWithContent('partner.pdf', validPdfBytes());

    $this->actingAs($user)
        ->post(route('partner.submissions.store'), [
            'device_type' => 'inverter',
            'manufacturer' => 'Sungrow',
            'product_name' => 'SG RT',
            'datasheet' => $file,
        ])
        ->assertRedirect(route('device-scan.uploads.compiled-records', ['datasheet' => 1]));

    $datasheet = DeviceDatasheet::query()->firstOrFail();
    expect($datasheet->source_type)->toBe('partner_submitted')
        ->and($datasheet->partner_id)->toBe($user->id)
        ->and($datasheet->datasheet_path)->toStartWith('device-scan/partners/partner-'.$user->id.'/submissions/datasheets/inverter/');
});

it('compile job creates compiled records from a module corpus fixture', function () {
    $fixture = base_path('storage/app/private/device-scan/corpus/modules/JinkoSolar/JinkoSolar-TigerNeo-JKM595-615N-78HL4-V-F1.pdf');

    if (! is_file($fixture)) {
        test()->markTestSkipped('Module corpus fixture is not present.');
    }

    Storage::disk('device-scan-test')->put('uploads/jinko.pdf', file_get_contents($fixture));
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'status' => 'security_checked',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/jinko.pdf',
        'datasheet_sha256' => hash_file('sha256', $fixture),
    ]);

    (new CompileDeviceDatasheetJob($datasheet->id, 'module'))->handle(
        app(ModuleCompiler::class),
        app(InverterCompiler::class),
        app(DeviceScanArtifactStorage::class),
    );

    expect($datasheet->fresh()->status)->toBeIn(['compiled', 'review_required'])
        ->and($datasheet->compiledRecords()->count())->toBeGreaterThan(0);
});

it('subscriber Canadian Solar upload passes module device type and compiles electrical records', function () {
    $fixture = base_path('storage/app/private/device-scan/corpus/modules/CanadianSolar/CanadianSolar-TOPBiHiKu6-CS6.2-66TB-590-620H.pdf');

    if (! is_file($fixture)) {
        test()->markTestSkipped('Canadian Solar module corpus fixture is not present.');
    }

    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);
    $file = new UploadedFile($fixture, 'CanadianSolar-TOPBiHiKu6.pdf', 'application/pdf', null, true);

    $this->actingAs($user)
        ->post(route('my-library.uploads.store'), [
            'device_type' => 'module',
            'manufacturer' => 'canadiansolar',
            'product_name' => 'TOPBiHiKu6',
            'datasheet' => $file,
        ])
        ->assertRedirect();

    $datasheet = DeviceDatasheet::query()->firstOrFail();
    $records = $datasheet->compiledRecords()->get();
    $payload = json_decode(Storage::disk($records->first()->compiled_disk)->get($records->first()->compiled_path), true);

    expect($datasheet->device_type)->toBe('module')
        ->and($datasheet->manufacturer)->toBe('Canadian Solar')
        ->and($datasheet->metadata['compile_selected_device_type'])->toBe('module')
        ->and($records->count())->toBeGreaterThan(0)
        ->and(data_get($payload, 'electrical_stc.models'))->not->toBeEmpty();
});

it('manufacturer upload uses account manufacturer even when compiler detects another manufacturer', function () {
    $fixture = base_path('storage/app/private/device-scan/corpus/modules/CanadianSolar/CanadianSolar-TOPBiHiKu6-CS6.2-66TB-590-620H.pdf');

    if (! is_file($fixture)) {
        test()->markTestSkipped('Canadian Solar module corpus fixture is not present.');
    }

    $company = ManufacturerCompany::create([
        'uuid' => (string) Str::uuid(),
        'name' => 'Vikram Solar',
        'slug' => 'vikram-solar',
        'plan_code' => 'pro',
        'subscription_status' => 'contract_active',
        'max_users' => 3,
        'metadata' => ['manufacturer_aliases' => ['Vikram Solar', 'Vikram']],
    ]);
    $user = User::factory()->create([
        'role' => LineWattRole::PARTNER_ADMIN,
        'manufacturer_company_id' => $company->id,
        'manufacturer_role' => 'manufacturer_admin',
    ]);
    $file = new UploadedFile($fixture, 'CanadianSolar-TOPBiHiKu6.pdf', 'application/pdf', null, true);

    $this->actingAs($user)
        ->post(route('partner.submissions.store'), [
            'device_type' => 'module',
            'manufacturer' => 'Canadian Solar',
            'product_name' => 'Uploaded under Vikram',
            'datasheet' => $file,
        ])
        ->assertRedirect();

    $record = CompiledDeviceRecord::query()->firstOrFail();

    expect($record->manufacturer)->toBe('Vikram Solar')
        ->and($record->metadata['detected_manufacturer'])->not->toBe('Vikram Solar')
        ->and($record->metadata['manufacturer_mismatch_detected'])->toBeTrue();
});

it('loads the compare shell for two private records of the same device type', function () {
    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'status' => 'compiled',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/module.pdf',
        'datasheet_sha256' => str_repeat('a', 64),
    ]);
    $first = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Jinko 600W',
        'power_class_w' => 600,
        'status' => 'compiled',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/jinko-600.json',
        'compiled_sha256' => str_repeat('b', 64),
    ]);
    $second = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'manufacturer' => 'LONGi',
        'display_name' => 'LONGi 600W',
        'power_class_w' => 600,
        'status' => 'compiled',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/longi-600.json',
        'compiled_sha256' => str_repeat('c', 64),
    ]);

    $this->actingAs($user)
        ->get(route('compare', ['records' => "{$first->id},{$second->id}"]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/Compare')
            ->has('records', 2)
            ->where('message', null)
        );
});

it('stores edited review json separately from compiled json', function () {
    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'status' => 'compiled',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/module.pdf',
        'datasheet_sha256' => str_repeat('d', 64),
        'metadata' => ['tenant_uuid' => 'tenant-'.$user->id],
    ]);
    $compiled = app(DeviceScanArtifactStorage::class)->storeCompiledJson(['manufacturer' => 'Jinko Solar'], [
        'source_type' => 'tenant_private',
        'tenant_uuid' => 'tenant-'.$user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'model_name' => 'JKM600',
        'compiled_uuid' => 'compiled-review-test',
    ]);
    $record = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Jinko 600W',
        'power_class_w' => 600,
        'status' => 'compiled',
        'compiled_disk' => $compiled['disk'],
        'compiled_path' => $compiled['path'],
        'compiled_sha256' => $compiled['sha256'],
    ]);

    $this->actingAs($user)
        ->patch(route('my-library.records.review.save', ['record' => $record->uuid]), [
            'sections' => [[
                'key' => 'identity',
                'title' => 'Identity',
                'rows' => [[
                    'path' => 'identity.display_name',
                    'field' => 'Display Name',
                    'value' => 'Corrected Jinko 600W',
                    'unit' => '',
                    'normalized' => '',
                    'page' => '',
                    'section' => '',
                    'sourceText' => 'source text',
                ]],
            ]],
        ])
        ->assertRedirect();

    $record->refresh();
    expect($record->compiled_path)->toBe($compiled['path'])
        ->and($record->metadata['review_status'])->toBe('pending_review')
        ->and($record->metadata['review_artifact']['path'])->not->toBe($compiled['path']);

    Storage::disk('device-scan-test')->assertExists($record->metadata['review_artifact']['path']);
});

it('streams the source pdf for the review preview', function () {
    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);
    Storage::disk('device-scan-test')->put('uploads/source.pdf', validPdfBytes());
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'status' => 'compiled',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/source.pdf',
        'datasheet_original_filename' => 'source.pdf',
        'datasheet_sha256' => str_repeat('e', 64),
        'metadata' => ['tenant_uuid' => 'tenant-'.$user->id],
    ]);
    $record = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'display_name' => 'Preview Test',
        'status' => 'compiled',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/preview-test.json',
        'compiled_sha256' => str_repeat('f', 64),
    ]);

    $this->actingAs($user)
        ->get(route('my-library.records.review.source-pdf', ['record' => $record->uuid]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('opens a private engineering record detail for the owning subscriber', function () {
    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'status' => 'compiled',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/source.pdf',
        'datasheet_sha256' => str_repeat('1', 64),
        'metadata' => ['tenant_uuid' => 'tenant-'.$user->id],
    ]);
    $compiled = app(DeviceScanArtifactStorage::class)->storeCompiledJson(['manufacturer' => 'Jinko Solar'], [
        'source_type' => 'tenant_private',
        'tenant_uuid' => 'tenant-'.$user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'model_name' => 'JKM600',
        'compiled_uuid' => 'private-open-record-test',
    ]);
    $record = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Private Jinko 600W',
        'status' => 'compiled',
        'compiled_disk' => $compiled['disk'],
        'compiled_path' => $compiled['path'],
        'compiled_sha256' => $compiled['sha256'],
    ]);

    $this->actingAs($user)
        ->get(route('records.show', ['record' => $record->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/EngineeringRecordDetail')
            ->where('record.display_name', 'Private Jinko 600W')
        );
});

it('normalizes common manufacturer variants to canonical names', function () {
    $normalizer = app(ManufacturerNormalizer::class);

    expect($normalizer->normalize('J A Solar')['name'])->toBe('JA Solar')
        ->and($normalizer->normalize('jasolar')['name'])->toBe('JA Solar')
        ->and($normalizer->normalize('canadiansolar')['name'])->toBe('Canadian Solar')
        ->and($normalizer->normalize('jinko')['name'])->toBe('Jinko Solar');
});

it('returns upload manufacturer autocomplete suggestions case insensitively', function () {
    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);

    CompiledDeviceRecord::create([
        'device_datasheet_id' => DeviceDatasheet::create([
            'source_type' => 'central_curated',
            'device_type' => 'module',
            'status' => 'compiled',
            'datasheet_disk' => 'device-scan-test',
            'datasheet_path' => 'uploads/source.pdf',
            'datasheet_sha256' => str_repeat('2', 64),
        ])->id,
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Canadian Solar',
        'display_name' => 'TOPBiHiKu6',
        'status' => 'compiled',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => 'compiled/canadian.json',
        'compiled_sha256' => str_repeat('3', 64),
    ]);

    $this->actingAs($user)
        ->getJson(route('uploads.manufacturers', ['q' => 'canAD', 'device_type' => 'module']))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Canadian Solar', 'value' => 'Canadian Solar']);
});

it('lets registered non subscribers download only permitted original datasheet pdfs', function () {
    $user = User::factory()->create(['role' => LineWattRole::GUEST]);
    Storage::disk('device-scan-test')->put('uploads/source.pdf', validPdfBytes());
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'status' => 'published',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/source.pdf',
        'datasheet_original_filename' => 'source.pdf',
        'datasheet_sha256' => str_repeat('4', 64),
        'pdf_access_mode' => 'hosted_with_permission',
        'permission_status' => 'allowed',
        'can_public_download' => true,
        'can_public_preview' => true,
        'can_internal_preview' => true,
        'can_private_download' => true,
    ]);
    $compiled = app(DeviceScanArtifactStorage::class)->storeCompiledJson(['manufacturer' => 'Jinko Solar'], [
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'model_name' => 'JKM600',
        'compiled_uuid' => 'export-access-test',
    ]);
    $record = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Jinko 600W',
        'status' => 'published',
        'compiled_disk' => $compiled['disk'],
        'compiled_path' => $compiled['path'],
        'compiled_sha256' => $compiled['sha256'],
    ]);

    $this->actingAs($user)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'datasheet']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->actingAs($user)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'csv']))
        ->assertForbidden();
});

it('lets subscribers export processed engineering data formats', function () {
    $user = User::factory()->create([
        'role' => LineWattRole::SUBSCRIBER,
        'plan_code' => 'demo_member',
        'subscription_status' => 'active',
    ]);
    $datasheet = DeviceDatasheet::create([
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'status' => 'compiled',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => 'uploads/source.pdf',
        'datasheet_sha256' => str_repeat('5', 64),
        'metadata' => ['tenant_uuid' => 'tenant-'.$user->id],
    ]);
    $compiled = app(DeviceScanArtifactStorage::class)->storeCompiledJson([
        'manufacturer' => 'Jinko Solar',
        'electrical_stc' => ['models' => [['display_name' => 'Jinko 600W', 'pmax' => ['value' => 600, 'unit' => 'W']]]],
    ], [
        'source_type' => 'tenant_private',
        'tenant_uuid' => 'tenant-'.$user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'model_name' => 'JKM600',
        'compiled_uuid' => 'subscriber-export-test',
    ]);
    $record = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'tenant_private',
        'tenant_id' => $user->id,
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'display_name' => 'Jinko 600W',
        'status' => 'compiled',
        'compiled_disk' => $compiled['disk'],
        'compiled_path' => $compiled['path'],
        'compiled_sha256' => $compiled['sha256'],
    ]);

    $this->actingAs($user)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'csv']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

function validPdfBytes(string $body = 'LineWatt upload test'): string
{
    return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n2 0 obj\n<< /Length ".strlen($body)." >>\nstream\n{$body}\nendstream\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
}
