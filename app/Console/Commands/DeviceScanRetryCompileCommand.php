<?php

namespace App\Console\Commands;

use App\Jobs\CompileDeviceDatasheetJob;
use App\Models\DeviceDatasheet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device-scan:retry-compile {device_datasheet_id : Device datasheet ID to recompile}')]
#[Description('Retry queued compilation for a stored LineWatt datasheet artifact.')]
class DeviceScanRetryCompileCommand extends Command
{
    public function handle(): int
    {
        $datasheet = DeviceDatasheet::query()->find($this->argument('device_datasheet_id'));

        if (! $datasheet instanceof DeviceDatasheet) {
            $this->error('Device datasheet not found.');

            return self::FAILURE;
        }

        if (! in_array($datasheet->device_type, ['module', 'inverter'], true)) {
            $this->error("Unsupported device type [{$datasheet->device_type}].");

            return self::FAILURE;
        }

        CompileDeviceDatasheetJob::dispatch($datasheet->id, $datasheet->device_type);

        $this->info("Compilation retry queued for datasheet #{$datasheet->id}.");

        return self::SUCCESS;
    }
}
