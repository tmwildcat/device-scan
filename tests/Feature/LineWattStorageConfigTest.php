<?php

use App\DeviceScan\Storage\DeviceScanPathBuilder;
use App\LineWatt\Storage\LineWattStorage;
use Illuminate\Support\Facades\Storage;

it('configures the LineWatt S3 compatible disk and derives DeviceScan paths from it', function () {
    Storage::fake('swefs');

    config([
        'filesystems.disks.swefs.driver' => 's3',
        'filesystems.disks.swefs.bucket' => 'linewatt-test',
        'filesystems.disks.swefs.endpoint' => 'https://nyc3.digitaloceanspaces.com',
        'linewatt-storage.disk' => 'swefs',
        'linewatt-storage.root' => 'swefs',
        'linewatt-storage.namespace' => 'common',
        'linewatt-storage.product' => 'line-watt-library',
        'linewatt-storage.base_path' => 'swefs/common/line-watt-library',
        'device-scan.storage_disk' => 'swefs',
        'device-scan.base_path' => 'swefs/common/line-watt-library/device-scan',
        'device-scan.central_path' => '{base_path}/central',
        'device-scan.tenant_path' => '{base_path}/tenants/{tenant_uuid}',
        'device-scan.partner_path' => '{base_path}/partners/{partner_uuid}',
    ]);

    $storage = app(LineWattStorage::class);
    $paths = app(DeviceScanPathBuilder::class);

    expect($storage->diskName())->toBe('swefs')
        ->and($storage->basePath('device-scan'))->toBe('swefs/common/line-watt-library/device-scan')
        ->and($paths->buildCompiledJsonPath([
            'source_type' => 'central_curated',
            'device_type' => 'module',
            'manufacturer' => 'Jinko Solar',
            'product_name' => 'Tiger Neo',
            'model_name' => 'JKM595N-78HL4',
            'compiled_uuid' => 'record-1',
        ]))->toBe('swefs/common/line-watt-library/device-scan/central/compiled/module/jinko-solar/tiger-neo/jkm595n-78hl4/record-1.json');
});
