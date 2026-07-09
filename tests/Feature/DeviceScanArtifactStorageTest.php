<?php

use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\DeviceScan\Storage\DeviceScanPathBuilder;
use App\DeviceScan\Storage\DeviceScanPromotionService;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('device-scan-test');
    config([
        'device-scan.storage_disk' => 'device-scan-test',
        'device-scan.base_path' => 'device-scan',
        'device-scan.central_path' => '{base_path}/central',
        'device-scan.tenant_path' => '{base_path}/tenants/{tenant_uuid}',
        'device-scan.partner_path' => '{base_path}/partners/{partner_uuid}',
    ]);
});

it('builds central tenant and partner artifact paths', function () {
    $paths = app(DeviceScanPathBuilder::class);

    expect($paths->buildDatasheetPath([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'datasheet_uuid' => 'ds-1',
        'extension' => 'pdf',
    ]))->toBe('device-scan/central/datasheets/module/jinko-solar/tiger-neo/ds-1.pdf');

    expect($paths->buildCompiledJsonPath([
        'source_type' => 'tenant_private',
        'tenant_uuid' => 'tenant-123',
        'device_type' => 'inverter',
        'manufacturer' => 'Sungrow',
        'product_name' => 'SG RT',
        'model_name' => 'SG10RT',
        'compiled_uuid' => 'cmp-1',
    ]))->toBe('device-scan/tenants/tenant-123/compiled/inverter/sungrow/sg-rt/sg10rt/cmp-1.json');

    expect($paths->buildDatasheetPath([
        'source_type' => 'partner_submitted',
        'partner_uuid' => 'partner-456',
        'device_type' => 'module',
        'manufacturer' => 'LONGi',
        'product_name' => 'Hi-MO X6',
        'datasheet_uuid' => 'ds-2',
        'extension' => 'PDF',
    ]))->toBe('device-scan/partners/partner-456/submissions/datasheets/module/longi/hi-mo-x6/ds-2.pdf');

    expect($paths->buildCompiledJsonPath([
        'source_type' => 'partner_submitted',
        'partner_uuid' => 'partner-456',
        'device_type' => 'module',
        'manufacturer' => 'LONGi',
        'product_name' => 'Hi-MO X6',
        'model_name' => 'LR7-72HTH',
        'compiled_uuid' => 'cmp-2',
    ]))->toBe('device-scan/partners/partner-456/compiled/module/longi/hi-mo-x6/lr7-72hth/cmp-2.json');
});

it('allows one datasheet to have multiple compiled records with json stored outside the database', function () {
    $storage = app(DeviceScanArtifactStorage::class);
    $datasheetArtifact = $storage->storeDatasheet('pdf-bytes', [
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'product_name' => 'Tiger Neo',
        'datasheet_uuid' => 'jinko-ds',
    ]);

    $datasheet = DeviceDatasheet::create([
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'series' => 'Tiger Neo',
        'product_name' => 'JKM595-615N',
        'status' => 'compiled',
        'datasheet_disk' => $datasheetArtifact['disk'],
        'datasheet_path' => $datasheetArtifact['path'],
        'datasheet_size_bytes' => $datasheetArtifact['size_bytes'],
        'datasheet_sha256' => $datasheetArtifact['sha256'],
    ]);

    foreach ([595, 600, 605] as $power) {
        $compiled = $storage->storeCompiledJson(['model_name' => "JKM{$power}N-78HL4", 'power_class_w' => $power], [
            'source_type' => 'central_curated',
            'device_type' => 'module',
            'manufacturer' => 'Jinko Solar',
            'product_name' => 'JKM595-615N',
            'model_name' => "JKM{$power}N-78HL4",
            'compiled_uuid' => "compiled-{$power}",
        ]);

        CompiledDeviceRecord::create([
            'device_datasheet_id' => $datasheet->id,
            'source_type' => 'central_curated',
            'device_type' => 'module',
            'manufacturer' => 'Jinko Solar',
            'series' => 'Tiger Neo',
            'model_series' => 'JKM595-615N-78HL4',
            'model_name' => "JKM{$power}N-78HL4",
            'display_name' => "JKM{$power}N-78HL4",
            'power_class_w' => $power,
            'status' => 'compiled',
            'compiled_disk' => $compiled['disk'],
            'compiled_path' => $compiled['path'],
            'compiled_sha256' => $compiled['sha256'],
        ]);
    }

    expect($datasheet->compiledRecords()->count())->toBe(3);
    expect(Schema::hasColumn('compiled_device_records', 'compiled_json'))->toBeFalse();

    $record = $datasheet->compiledRecords()->where('power_class_w', 600)->firstOrFail();
    expect($storage->readCompiledJson($record->compiled_path, $record->compiled_disk))
        ->toMatchArray(['model_name' => 'JKM600N-78HL4', 'power_class_w' => 600]);
});

it('promotes tenant records by copying artifacts and creating central review rows', function () {
    $storage = app(DeviceScanArtifactStorage::class);
    $tenantUuid = 'tenant-abc';
    $datasheetArtifact = $storage->storeDatasheet('tenant-pdf', [
        'source_type' => 'tenant_private',
        'tenant_uuid' => $tenantUuid,
        'device_type' => 'inverter',
        'manufacturer' => 'Huawei',
        'product_name' => 'SUN2000',
        'datasheet_uuid' => 'tenant-ds',
    ]);
    $compiledArtifact = $storage->storeCompiledJson(['model_name' => 'SUN2000-10KTL-M2'], [
        'source_type' => 'tenant_private',
        'tenant_uuid' => $tenantUuid,
        'device_type' => 'inverter',
        'manufacturer' => 'Huawei',
        'product_name' => 'SUN2000',
        'model_name' => 'SUN2000-10KTL-M2',
        'compiled_uuid' => 'tenant-cmp',
    ]);

    $datasheet = DeviceDatasheet::create([
        'source_type' => 'tenant_private',
        'tenant_id' => 10,
        'device_type' => 'inverter',
        'manufacturer' => 'Huawei',
        'series' => 'SUN2000',
        'product_name' => 'SUN2000 KTL-M2',
        'status' => 'compiled',
        'datasheet_disk' => $datasheetArtifact['disk'],
        'datasheet_path' => $datasheetArtifact['path'],
        'datasheet_size_bytes' => $datasheetArtifact['size_bytes'],
        'datasheet_sha256' => $datasheetArtifact['sha256'],
        'metadata' => ['tenant_uuid' => $tenantUuid],
    ]);
    $record = CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'tenant_private',
        'tenant_id' => 10,
        'device_type' => 'inverter',
        'manufacturer' => 'Huawei',
        'series' => 'SUN2000',
        'model_series' => 'SUN2000 KTL-M2',
        'model_name' => 'SUN2000-10KTL-M2',
        'display_name' => 'SUN2000-10KTL-M2',
        'power_class_kw' => 10,
        'status' => 'compiled',
        'compiled_disk' => $compiledArtifact['disk'],
        'compiled_path' => $compiledArtifact['path'],
        'compiled_sha256' => $compiledArtifact['sha256'],
    ]);

    $result = app(DeviceScanPromotionService::class)->promoteToCentral($record);

    expect($result['datasheet']->source_type)->toBe('central_curated')
        ->and($result['datasheet']->status)->toBe('review_required')
        ->and($result['record']->source_type)->toBe('central_curated')
        ->and($result['record']->status)->toBe('review_required')
        ->and($record->fresh()->source_type)->toBe('tenant_private');

    expect($storage->exists($datasheet->datasheet_path, $datasheet->datasheet_disk))->toBeTrue();
    expect($storage->exists($record->compiled_path, $record->compiled_disk))->toBeTrue();
    expect($storage->exists($result['datasheet']->datasheet_path, $result['datasheet']->datasheet_disk))->toBeTrue();
    expect($storage->exists($result['record']->compiled_path, $result['record']->compiled_disk))->toBeTrue();
    expect($result['record']->compiled_path)->toStartWith('device-scan/central/compiled/inverter/');
});
