<?php

namespace App\Console\Commands;

use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device-scan:move-artifact {--from-disk=} {--from-path=} {--to-disk=} {--to-path=} {--copy} {--delete-source}')]
#[Description('Copy or move a DeviceScan artifact between storage paths.')]
class DeviceScanMoveArtifactCommand extends Command
{
    public function handle(DeviceScanArtifactStorage $storage): int
    {
        $fromDisk = (string) ($this->option('from-disk') ?: $storage->defaultDisk());
        $toDisk = (string) ($this->option('to-disk') ?: $fromDisk);
        $fromPath = (string) $this->option('from-path');
        $toPath = (string) $this->option('to-path');

        if ($fromPath === '' || $toPath === '') {
            $this->error('Both --from-path and --to-path are required.');

            return self::FAILURE;
        }

        if (! $storage->exists($fromPath, $fromDisk)) {
            $this->error("Source artifact does not exist: {$fromDisk}:{$fromPath}");

            return self::FAILURE;
        }

        if ($this->option('copy') || ! $this->option('delete-source')) {
            $storage->copy($fromPath, $toPath, $fromDisk, $toDisk);
            $this->info("Copied artifact to {$toDisk}:{$toPath}");

            if ($this->option('delete-source')) {
                $storage->delete($fromPath, $fromDisk);
                $this->info("Deleted source {$fromDisk}:{$fromPath}");
            }

            return self::SUCCESS;
        }

        $storage->move($fromPath, $toPath, $fromDisk, $toDisk);
        $this->info("Moved artifact to {$toDisk}:{$toPath}");

        return self::SUCCESS;
    }
}
