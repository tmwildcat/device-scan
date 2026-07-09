<?php

namespace App\Console\Commands;

use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device-scan:rebuild-hashes {--datasheets} {--compiled} {--limit=100}')]
#[Description('Recompute sha256 hashes for stored DeviceScan artifacts.')]
class DeviceScanRebuildHashesCommand extends Command
{
    public function handle(DeviceScanArtifactStorage $storage): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $datasheets = (bool) $this->option('datasheets');
        $compiled = (bool) $this->option('compiled');

        if (! $datasheets && ! $compiled) {
            $datasheets = $compiled = true;
        }

        if ($datasheets) {
            DeviceDatasheet::query()->limit($limit)->each(function (DeviceDatasheet $datasheet) use ($storage): void {
                if ($storage->exists($datasheet->datasheet_path, $datasheet->datasheet_disk)) {
                    $datasheet->forceFill([
                        'datasheet_sha256' => $storage->calculateSha256($datasheet->datasheet_path, $datasheet->datasheet_disk),
                    ])->save();
                }
            });
        }

        if ($compiled) {
            CompiledDeviceRecord::query()->limit($limit)->each(function (CompiledDeviceRecord $record) use ($storage): void {
                if ($storage->exists($record->compiled_path, $record->compiled_disk)) {
                    $record->forceFill([
                        'compiled_sha256' => $storage->calculateSha256($record->compiled_path, $record->compiled_disk),
                    ])->save();
                }
            });
        }

        $this->info('DeviceScan artifact hashes rebuilt.');

        return self::SUCCESS;
    }
}
