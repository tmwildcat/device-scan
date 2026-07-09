<?php

namespace App\Console\Commands;

use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\DeviceScan\Storage\DeviceScanPathBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('device-scan:storage-check')]
#[Description('Check the configured DeviceScan artifact storage disk.')]
class DeviceScanStorageCheckCommand extends Command
{
    public function handle(DeviceScanArtifactStorage $storage, DeviceScanPathBuilder $paths): int
    {
        $disk = $storage->defaultDisk();
        $path = trim((string) config('device-scan.base_path'), '/').'/_storage-check/'.Str::uuid().'.txt';

        $storage->storeReviewJson(['ok' => true], ['path' => $path], $disk);

        if (! $storage->exists($path, $disk)) {
            $this->error("Storage check failed for disk [{$disk}].");

            return self::FAILURE;
        }

        $storage->delete($path, $disk);

        $this->info("DeviceScan storage disk: {$disk}");
        $this->line('Base path: '.config('device-scan.base_path'));
        $this->line('Central path: '.$paths->centralRoot());
        $this->line('Tenant path template: '.config('device-scan.tenant_path'));
        $this->line('Partner path template: '.config('device-scan.partner_path'));

        return self::SUCCESS;
    }
}
