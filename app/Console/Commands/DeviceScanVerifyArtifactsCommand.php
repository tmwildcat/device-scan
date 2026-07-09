<?php

namespace App\Console\Commands;

use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device-scan:verify-artifacts {--source-type=} {--device-type=}')]
#[Description('Verify that DeviceScan artifact paths stored in the database exist.')]
class DeviceScanVerifyArtifactsCommand extends Command
{
    public function handle(DeviceScanArtifactStorage $storage): int
    {
        $sourceType = $this->option('source-type');
        $deviceType = $this->option('device-type');
        $missing = 0;
        $checked = 0;

        DeviceDatasheet::query()
            ->when($sourceType, fn ($query) => $query->where('source_type', $sourceType))
            ->when($deviceType, fn ($query) => $query->where('device_type', $deviceType))
            ->each(function (DeviceDatasheet $datasheet) use ($storage, &$missing, &$checked): void {
                $checked++;
                if (! $storage->exists($datasheet->datasheet_path, $datasheet->datasheet_disk)) {
                    $missing++;
                    $this->warn("Missing datasheet {$datasheet->id}: {$datasheet->datasheet_disk}:{$datasheet->datasheet_path}");
                }
            });

        CompiledDeviceRecord::query()
            ->when($sourceType, fn ($query) => $query->where('source_type', $sourceType))
            ->when($deviceType, fn ($query) => $query->where('device_type', $deviceType))
            ->each(function (CompiledDeviceRecord $record) use ($storage, &$missing, &$checked): void {
                $checked++;
                if (! $storage->exists($record->compiled_path, $record->compiled_disk)) {
                    $missing++;
                    $this->warn("Missing compiled record {$record->id}: {$record->compiled_disk}:{$record->compiled_path}");
                }
            });

        $this->info("Checked {$checked} artifacts; missing {$missing}.");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
