<?php

namespace App\Console\Commands;

use App\DeviceScan\Storage\DeviceScanPromotionService;
use App\Models\CompiledDeviceRecord;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device-scan:promote-partner-submission {compiled_device_record_id}')]
#[Description('Copy a partner submitted record and datasheet into central curated review records.')]
class DeviceScanPromotePartnerSubmissionCommand extends Command
{
    public function handle(DeviceScanPromotionService $promotion): int
    {
        $record = CompiledDeviceRecord::query()->findOrFail((int) $this->argument('compiled_device_record_id'));

        if ($record->source_type !== 'partner_submitted') {
            $this->error('Record is not a partner_submitted compiled device record.');

            return self::FAILURE;
        }

        $result = $promotion->promoteToCentral($record);
        $this->info('Created central datasheet ID: '.$result['datasheet']->id);
        $this->info('Created central compiled record ID: '.$result['record']->id);

        return self::SUCCESS;
    }
}
